<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Belediye kullanıcı / müdürlük CSV içe aktarımı.
 *
 * CSV sütunları: name, email, role, directorate_id, assigned_directorate_ids
 * - directorate_id dolu + rol «Müdürlük» → müdürlük kaydı + birim yöneticisi (manager) hesabı
 * - directorate_id boş + rol «Müdürlük» → saha şefi (staff) hesabı
 * - rol boş + kişi adı → başkan yardımcısı (ilk iki) veya saha şefi (diğerleri, bağlamdan)
 */
class ImportMunicipalityUsersFromCsv
{
    /** @var list<string> */
    protected array $keepAdminEmails = ['admin@admin.com'];

    protected string $defaultPassword = 'password';

    /**
     * @return array{departments: int, vice_mayors: int, managers: int, foremen: int, removed_users: int}
     */
    public function import(string $csvPath, ?string $keepAdminEmail = null): array
    {
        if (! is_readable($csvPath)) {
            throw new RuntimeException("CSV okunamıyor: {$csvPath}");
        }

        if ($keepAdminEmail !== null) {
            $this->keepAdminEmails = array_values(array_unique([
                strtolower(trim($keepAdminEmail)),
                ...$this->keepAdminEmails,
            ]));
        }

        $rows = $this->parseCsv($csvPath);

        return DB::transaction(function () use ($rows): array {
            $removedUsers = $this->cleanExistingData();

            /** @var array<int, Department> $departmentsByLegacyId */
            $departmentsByLegacyId = [];
            $viceMayors = [];
            $foremanCandidates = [];

            foreach ($rows as $row) {
                if ($this->isAdminRow($row)) {
                    $this->ensureAdmin($row);

                    continue;
                }

                if ($this->isViceMayorRow($row)) {
                    $viceMayors[] = $this->createViceMayor($row);

                    continue;
                }

                if ($this->isAnalizRow($row)) {
                    continue;
                }

                if ($this->isDepartmentManagerRow($row)) {
                    $legacyId = (int) $row['directorate_id'];
                    if (! isset($departmentsByLegacyId[$legacyId])) {
                        [$legacyId, $department] = $this->createDepartmentWithManager($row);
                        $departmentsByLegacyId[$legacyId] = $department;
                    }

                    continue;
                }

                if ($this->isForemanAccountRow($row)) {
                    $foremanCandidates[] = ['row' => $row, 'legacy_id' => null];

                    continue;
                }

                if ($this->isNamedForemanRow($row)) {
                    $foremanCandidates[] = ['row' => $row, 'legacy_id' => $this->inferLegacyIdFromContext($row, $rows)];
                }
            }

            $this->assignViceMayorsToDepartments($viceMayors, $departmentsByLegacyId);

            $foremenCount = 0;
            foreach ($foremanCandidates as $candidate) {
                $foreman = $this->createForeman($candidate['row'], $departmentsByLegacyId, $candidate['legacy_id']);
                if ($foreman !== null) {
                    $foremenCount++;
                }
            }

            return [
                'departments' => count($departmentsByLegacyId),
                'vice_mayors' => count($viceMayors),
                'managers' => count($departmentsByLegacyId),
                'foremen' => $foremenCount,
                'removed_users' => $removedUsers,
            ];
        });
    }

    protected function cleanExistingData(): int
    {
        ApprovalRequest::query()->delete();
        Task::query()->delete();
        ClassifierTrainingSample::query()->delete();

        Department::query()->update([
            'vice_mayor_id' => null,
            'foreman_user_id' => null,
        ]);

        $removed = User::query()
            ->whereNotIn(DB::raw('LOWER(email)'), collect($this->keepAdminEmails)->map(fn ($e) => strtolower($e))->all())
            ->delete();

        Department::query()->delete();

        return $removed;
    }

    /**
     * @return list<array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}>
     */
    protected function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("CSV açılamadı: {$path}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($c) => strtolower(trim((string) $c)), $header);
        $indexes = array_flip($header);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $name = trim((string) ($data[$indexes['name'] ?? 0] ?? ''));
            $email = strtolower(trim((string) ($data[$indexes['email'] ?? 1] ?? '')));
            if ($name === '' && $email === '') {
                continue;
            }

            $directorateRaw = trim((string) ($data[$indexes['directorate_id'] ?? 3] ?? ''));
            $assignedRaw = trim((string) ($data[$indexes['assigned_directorate_ids'] ?? 4] ?? ''));

            $rows[] = [
                'name' => $name,
                'email' => $email,
                'role' => trim((string) ($data[$indexes['role'] ?? 2] ?? '')),
                'directorate_id' => $directorateRaw !== '' ? (int) $directorateRaw : null,
                'assigned_directorate_ids' => $this->parseIdList($assignedRaw),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<int>
     */
    protected function parseIdList(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', preg_split('/[;,]/', $raw) ?: [])));
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isAdminRow(array $row): bool
    {
        return strtolower($row['role']) === 'admin'
            || in_array($row['email'], $this->keepAdminEmails, true);
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isViceMayorRow(array $row): bool
    {
        if ($row['role'] !== '' || ! $this->looksLikePersonName($row['name'])) {
            return false;
        }

        $viceMayorEmails = [
            'aydemircan@kirklareli.bel.tr',
            'burak.suzulmus@kirklareli.bel.tr',
        ];

        return in_array($row['email'], $viceMayorEmails, true);
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isAnalizRow(array $row): bool
    {
        return str_contains(mb_strtolower($row['role']), 'analiz');
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isDepartmentManagerRow(array $row): bool
    {
        return $this->isMudurlukRole($row) && $row['directorate_id'] !== null;
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isForemanAccountRow(array $row): bool
    {
        return $this->isMudurlukRole($row) && $row['directorate_id'] === null;
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isNamedForemanRow(array $row): bool
    {
        return $row['role'] === ''
            && $this->looksLikePersonName($row['name'])
            && ! $this->isViceMayorRow($row)
            && ! $this->isAnalizRow($row);
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function isMudurlukRole(array $row): bool
    {
        return mb_strtolower($row['role']) === 'müdürlük'
            || mb_strtolower($row['role']) === 'mudurluk';
    }

    protected function looksLikePersonName(string $name): bool
    {
        return (bool) preg_match('/\p{L}+\s+\p{L}+/u', trim($name));
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function ensureAdmin(array $row): void
    {
        $email = $row['email'] !== '' ? $row['email'] : 'admin@admin.com';

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $row['name'] !== '' ? $row['name'] : 'admin',
                'password' => Hash::make($this->defaultPassword),
                'role' => UserRole::Admin,
                'department_id' => null,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     */
    protected function createViceMayor(array $row): User
    {
        return User::query()->create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => Hash::make($this->defaultPassword),
            'role' => UserRole::ViceMayor,
            'department_id' => null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     * @return array{0: int, 1: Department}
     */
    protected function createDepartmentWithManager(array $row): array
    {
        $legacyId = (int) $row['directorate_id'];
        $departmentName = $this->canonicalDepartmentName($row['name']);

        $department = Department::query()->create([
            'name' => $departmentName,
            'staff_count' => 0,
        ]);

        $manager = User::query()->create([
            'name' => $departmentName.' — Birim yöneticisi',
            'email' => $row['email'],
            'password' => Hash::make($this->defaultPassword),
            'role' => UserRole::Manager,
            'department_id' => $department->id,
            'email_verified_at' => now(),
        ]);

        $department->update([
            'manager_name' => $manager->name,
        ]);

        return [$legacyId, $department];
    }

    /**
     * @param  list<User>  $viceMayors
     * @param  array<int, Department>  $departmentsByLegacyId
     */
    protected function assignViceMayorsToDepartments(array $viceMayors, array $departmentsByLegacyId): void
    {
        if ($viceMayors === []) {
            return;
        }

        ksort($departmentsByLegacyId);
        $legacyIds = array_keys($departmentsByLegacyId);
        $half = (int) ceil(count($legacyIds) / 2);

        foreach ($legacyIds as $index => $legacyId) {
            $viceMayor = $viceMayors[$index < $half ? 0 : min(1, count($viceMayors) - 1)];
            $departmentsByLegacyId[$legacyId]->update(['vice_mayor_id' => $viceMayor->id]);
        }
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     * @param  array<int, Department>  $departmentsByLegacyId
     */
    protected function createForeman(array $row, array $departmentsByLegacyId, ?int $legacyId): ?User
    {
        $department = null;
        if ($legacyId !== null && isset($departmentsByLegacyId[$legacyId])) {
            $department = $departmentsByLegacyId[$legacyId];
        } else {
            $canonical = $this->canonicalDepartmentName($row['name']);
            $department = collect($departmentsByLegacyId)->first(
                fn (Department $dept): bool => $dept->name === $canonical,
            );
        }

        if ($department === null) {
            return null;
        }

        $displayName = $this->looksLikePersonName($row['name'])
            ? $row['name']
            : $department->name.' — Saha şefi';

        $foreman = User::query()->create([
            'name' => $displayName,
            'email' => $row['email'],
            'password' => Hash::make($this->defaultPassword),
            'role' => UserRole::Staff,
            'department_id' => $department->id,
            'email_verified_at' => now(),
        ]);

        $department->update([
            'foreman_user_id' => $foreman->id,
            'foreman_name' => $this->looksLikePersonName($row['name']) ? $row['name'] : $department->name.' saha şefi',
        ]);

        return $foreman;
    }

    /**
     * @param  array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}  $row
     * @param  list<array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}>  $allRows
     */
    protected function inferLegacyIdFromContext(array $row, array $allRows): ?int
    {
        $index = null;
        foreach ($allRows as $i => $candidate) {
            if ($candidate['email'] === $row['email'] && $candidate['name'] === $row['name']) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return null;
        }

        for ($i = $index - 1; $i >= 0; $i--) {
            if ($allRows[$i]['directorate_id'] !== null) {
                return $allRows[$i]['directorate_id'];
            }
            if ($this->isForemanAccountRow($allRows[$i])) {
                return $this->matchLegacyIdByDepartmentName($allRows[$i]['name'], $allRows);
            }
        }

        return null;
    }

    /**
     * @param  list<array{name: string, email: string, role: string, directorate_id: ?int, assigned_directorate_ids: list<int>}>  $allRows
     */
    protected function matchLegacyIdByDepartmentName(string $name, array $allRows): ?int
    {
        $canonical = $this->canonicalDepartmentName($name);
        foreach ($allRows as $row) {
            if ($row['directorate_id'] !== null && $this->canonicalDepartmentName($row['name']) === $canonical) {
                return $row['directorate_id'];
            }
        }

        return null;
    }

    protected function canonicalDepartmentName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }

        if (preg_match('/müdürlüğü\s*$/iu', $name)) {
            return $name;
        }

        if (preg_match('/müdürlük\s*$/iu', $name)) {
            return $name.'ü';
        }

        return $name.' Müdürlüğü';
    }
}
