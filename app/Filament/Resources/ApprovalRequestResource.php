<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalRequestResource\Pages;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Raporlar';

    protected static ?string $navigationLabel = 'Onay talepleri';

    protected static ?string $modelLabel = 'onay talebi';

    protected static ?string $pluralModelLabel = 'onay talepleri';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isViceMayor();
    }

    public static function canCreate(): bool
    {
        return false;
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
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Talep tipi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'task_close' => 'Görev kapanışı',
                        'user_role_change' => 'Kullanıcı rol değişimi',
                        'department_vice_mayor_change' => 'Müdürlük sorumluluk değişimi',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ApprovalRequest::STATUS_PENDING => 'Bekliyor',
                        ApprovalRequest::STATUS_APPROVED => 'Onaylandı',
                        ApprovalRequest::STATUS_REJECTED => 'Reddedildi',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('requester.name')->label('Talep eden')->placeholder('—'),
                Tables\Columns\TextColumn::make('approver.name')->label('Onaylayan')->placeholder('—'),
                Tables\Columns\TextColumn::make('reason')->label('Gerekçe')->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ApprovalRequest::STATUS_PENDING => 'Bekliyor',
                        ApprovalRequest::STATUS_APPROVED => 'Onaylandı',
                        ApprovalRequest::STATUS_REJECTED => 'Reddedildi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalRequest::STATUS_PENDING)
                    ->action(function (ApprovalRequest $record): void {
                        self::applyApproval($record);
                        Notification::make()->title('Talep onaylandı')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalRequest::STATUS_PENDING)
                    ->action(function (ApprovalRequest $record): void {
                        $record->update([
                            'status' => ApprovalRequest::STATUS_REJECTED,
                            'approved_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('Talep reddedildi')->warning()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovalRequests::route('/'),
        ];
    }

    protected static function applyApproval(ApprovalRequest $record): void
    {
        if ($record->type === 'user_role_change') {
            $target = User::query()->find($record->approvable_id);
            if ($target) {
                $target->update(['role' => $record->payload['to_role'] ?? $target->role->value]);
            }
        } elseif ($record->type === 'department_vice_mayor_change') {
            $target = Department::query()->find($record->approvable_id);
            if ($target) {
                $target->update(['vice_mayor_id' => $record->payload['to_vice_mayor_id'] ?? $target->vice_mayor_id]);
            }
        } elseif ($record->type === 'task_close') {
            $target = Task::query()->find($record->approvable_id);
            if ($target && $target->status->value === 'onay_bekliyor') {
                $target->update(['status' => \App\Enums\TaskStatus::Kapatildi]);
            }
        }

        $record->update([
            'status' => ApprovalRequest::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
}
