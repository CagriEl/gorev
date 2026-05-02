<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Raporlar';

    protected static ?string $navigationLabel = 'Denetim kayıtları';

    protected static ?string $modelLabel = 'denetim kaydı';

    protected static ?string $pluralModelLabel = 'denetim kayıtları';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isViceMayor();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zaman')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Kullanıcı')
                    ->placeholder('Sistem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Varlık')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'task' => 'Görev',
                        'user' => 'Kullanıcı',
                        'department' => 'Müdürlük',
                        'approval_request' => 'Onay talebi',
                        default => (string) ($state ?? '—'),
                    })
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('İşlem')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Oluşturma',
                        'updated' => 'Güncelleme',
                        'deleted' => 'Silme',
                        default => (string) ($state ?? 'Güncelleme'),
                    })
                    ->badge()
                    ->placeholder('Güncelleme')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->wrap(),
                Tables\Columns\TextColumn::make('properties.request_context.route')
                    ->label('İstek yolu')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Varlık')
                    ->options([
                        'task' => 'Görev',
                        'user' => 'Kullanıcı',
                        'department' => 'Müdürlük',
                        'approval_request' => 'Onay talebi',
                    ]),
                Tables\Filters\SelectFilter::make('event')
                    ->label('İşlem')
                    ->options([
                        'created' => 'Oluşturma',
                        'updated' => 'Güncelleme',
                        'deleted' => 'Silme',
                    ]),
                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Başlangıç'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('CSV dışa aktar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $user = auth()->user();
                        abort_unless($user?->isAdmin() || $user?->isViceMayor(), 403);

                        $rows = Activity::query()
                            ->latest('id')
                            ->limit(2000)
                            ->get()
                            ->map(function (Activity $item): array {
                                return [
                                    'id' => $item->id,
                                    'time' => (string) $item->created_at,
                                    'log_name' => (string) $item->log_name,
                                    'event' => (string) ($item->event ?? ''),
                                    'description' => (string) $item->description,
                                    'causer' => (string) ($item->causer?->name ?? 'Sistem'),
                                ];
                            });

                        $csv = "id,time,log_name,event,description,causer\n";
                        foreach ($rows as $row) {
                            $escaped = array_map(function (string $value): string {
                                return '"'.str_replace('"', '""', $value).'"';
                            }, $row);
                            $csv .= implode(',', $escaped)."\n";
                        }

                        return Response::streamDownload(function () use ($csv): void {
                            echo $csv;
                        }, 'audit-log.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
