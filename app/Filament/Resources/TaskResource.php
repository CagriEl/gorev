<?php

namespace App\Filament\Resources;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'görev';

    protected static ?string $pluralModelLabel = 'görevler';

    protected static ?string $navigationLabel = 'Görevler';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Görev')
                    ->schema([
                        Forms\Components\TextInput::make('task_code')
                            ->label('Görev kodu')
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                        Forms\Components\TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('department_id')
                            ->label('Müdürlük')
                            ->relationship(
                                name: 'department',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) {
                                    $user = auth()->user();
                                    if ($user?->role === UserRole::ViceMayor) {
                                        $query->where('vice_mayor_id', $user->id);
                                    } elseif ($user?->role === UserRole::Manager) {
                                        $query->whereKey($user->department_id);
                                    }
                                },
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('assignee_id')
                            ->label('Atanan personel')
                            ->relationship(
                                'assignee',
                                'name',
                                modifyQueryUsing: function (Builder $query) {
                                    $query->where('role', UserRole::Staff);
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('location')
                            ->label('Adres / konum')
                            ->maxLength(255),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Enlem')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->rule('required_with:longitude')
                                    ->rules(['nullable', 'numeric', 'between:-90,90'])
                                    ->validationMessages([
                                        'required_with' => 'Boylam girildiğinde enlem de zorunludur.',
                                        'numeric' => 'Enlem sayısal bir değer olmalıdır.',
                                        'between' => 'Enlem −90 ile 90 derece arasında olmalıdır.',
                                    ])
                                    ->extraInputAttributes(['x-on:blur' => '$dispatch(\'leaflet-sync-from-form\')']),
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Boylam')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->rule('required_with:latitude')
                                    ->rules(['nullable', 'numeric', 'between:-180,180'])
                                    ->validationMessages([
                                        'required_with' => 'Enlem girildiğinde boylam da zorunludur.',
                                        'numeric' => 'Boylam sayısal bir değer olmalıdır.',
                                        'between' => 'Boylam −180 ile 180 derece arasında olmalıdır.',
                                    ])
                                    ->extraInputAttributes(['x-on:blur' => '$dispatch(\'leaflet-sync-from-form\')']),
                            ]),
                        Forms\Components\ViewField::make('location_map')
                            ->view('filament.forms.components.leaflet-location-picker')
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->viewData(fn (Get $get): array => [
                                'lat' => (float) ($get('latitude') ?: 41.7351),
                                'lng' => (float) ($get('longitude') ?: 27.2252),
                            ]),
                        Forms\Components\Select::make('priority')
                            ->label('Öncelik')
                            ->options(TaskPriority::class)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(TaskStatus::class)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('solution_note')
                            ->label('Çözüm notu')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Tabs::make('task_photos')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Göreve varış fotoğrafı')
                                    ->schema([
                                        Forms\Components\FileUpload::make('arrival_photos')
                                            ->label('Varış fotoğrafları')
                                            ->multiple()
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->image()
                                            ->directory('task-arrival-photos'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Görev sonrası fotoğraf')
                                    ->schema([
                                        Forms\Components\FileUpload::make('completion_photos')
                                            ->label('Sonrası fotoğrafları')
                                            ->multiple()
                                            ->image()
                                            ->directory('task-completion-photos'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Atanma'),
                        Forms\Components\DateTimePicker::make('dispatched_at')
                            ->label('Sahaya çıkış'),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Tamamlanma'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Özet')
                    ->schema([
                        Infolists\Components\TextEntry::make('task_code')->label('Kod'),
                        Infolists\Components\TextEntry::make('title')->label('Başlık'),
                        Infolists\Components\TextEntry::make('department.name')->label('Müdürlük'),
                        Infolists\Components\TextEntry::make('assignee.name')->label('Atanan')->placeholder('—'),
                        Infolists\Components\TextEntry::make('location')->label('Konum'),
                        Infolists\Components\TextEntry::make('priority')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof TaskPriority ? (string) ($state->getLabel() ?? $state->value) : (string) $state),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof TaskStatus ? (string) ($state->getLabel() ?? $state->value) : (string) $state),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Durum geçmişi')
                    ->schema([
                        Infolists\Components\ViewEntry::make('activity_timeline')
                            ->view('filament.infolists.components.task-activity-timeline')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->description(fn (Task $record): ?string => $record->location)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Birim')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Öncelik')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TaskPriority ? $state->getLabel() ?? '' : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof TaskPriority ? $state : TaskPriority::tryFrom((string) $state)) {
                        TaskPriority::Kritik => 'danger',
                        TaskPriority::Yuksek => 'primary',
                        TaskPriority::Normal => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TaskStatus ? $state->getLabel() ?? '' : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof TaskStatus ? $state : TaskStatus::tryFrom((string) $state)) {
                        TaskStatus::Sahada => 'warning',
                        TaskStatus::Yonlendirildi => 'info',
                        TaskStatus::Cozuldu => 'primary',
                        TaskStatus::OnayBekliyor => 'warning',
                        TaskStatus::Kapatildi => 'success',
                        TaskStatus::Tamamlandi => 'success',
                        TaskStatus::Bekliyor => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Atanma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('dispatched_at')
                    ->label('Sahaya çıkış')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Tamamlanma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sla_duration')
                    ->label('Çözüm (dk)')
                    ->getStateUsing(fn (Task $record): ?int => $record->resolveDurationMinutes())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('resolve_minutes', $direction))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('sla_badge')
                    ->label('SLA')
                    ->badge()
                    ->getStateUsing(function (Task $record): ?string {
                        if (! in_array($record->status, [TaskStatus::Tamamlandi, TaskStatus::Kapatildi], true)) {
                            return null;
                        }
                        $ok = $record->slaWithinTarget(Task::SLA_TARGET_MINUTES);

                        return match ($ok) {
                            true => 'Başarılı',
                            false => 'Süre Aşıldı',
                            default => null,
                        };
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'Başarılı' => 'success',
                        'Süre Aşıldı' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Task $record): bool => auth()->user()?->can('update', $record) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(false),
                ]),
            ])
            ->defaultSort('assigned_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['task_code', 'title', 'location'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Task $record */
        return [
            'Birim' => $record->department?->name ?? '',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user?->role === UserRole::ViceMayor) {
            $ids = $user->managedDepartments()->pluck('id')->all();
            $query->whereIn('department_id', $ids);
        } elseif ($user?->role === UserRole::Manager && $user->department_id) {
            $query->where('department_id', $user->department_id);
        } elseif ($user?->role === UserRole::Staff) {
            $query->where('assignee_id', $user->id);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Task::class) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
