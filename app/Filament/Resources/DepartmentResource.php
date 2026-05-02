<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use App\Models\User;
use App\Support\Masking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'müdürlük';

    protected static ?string $pluralModelLabel = 'müdürlükler';

    protected static ?string $navigationLabel = 'Müdürlükler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Birim')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Müdürlük adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('vice_mayor_id')
                            ->label('Başkan yardımcısı')
                            ->relationship(
                                name: 'viceMayor',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('role', UserRole::ViceMayor),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('manager_name')
                            ->label('Birim müdürü')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('manager_phone')
                            ->label('Müdür telefon')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('foreman_name')
                            ->label('Saha şefi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('foreman_phone')
                            ->label('Saha şefi telefon')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Select::make('foreman_user_id')
                            ->label('Saha şefi kullanıcısı')
                            ->helperText('Bu müdürlüğe bağlı panel kullanıcısı; görevler bu kişiye atanır.')
                            ->options(function ($livewire): array {
                                $record = $livewire->getRecord();
                                if (! $record instanceof Department || ! $record->exists) {
                                    return [];
                                }

                                return User::query()
                                    ->where('department_id', $record->id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('staff_count')
                            ->label('Personel sayısı')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Müdürlük adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('viceMayor.name')
                    ->label('Başkan yardımcısı')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('manager_name')
                    ->label('Birim müdürü')
                    ->description(function (Department $record): ?string {
                        $user = auth()->user();
                        if ($user?->isAdmin() || $user?->isViceMayor()) {
                            return $record->manager_phone;
                        }

                        return Masking::phone($record->manager_phone);
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('foremanUser.name')
                    ->label('Saha şefi (hesap)')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('foreman_name')
                    ->label('Saha şefi (iletişim)')
                    ->description(function (Department $record): ?string {
                        $user = auth()->user();
                        if ($user?->isAdmin() || $user?->isViceMayor()) {
                            return $record->foreman_phone;
                        }

                        return Masking::phone($record->foreman_phone);
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Personel')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Department $record): bool => auth()->user()?->can('update', $record) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'manager_name', 'foreman_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Department $record */
        return [
            'Müdür' => $record->manager_name ?? '',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('foremanUser:id,name');
        $user = auth()->user();
        if ($user?->role === UserRole::ViceMayor) {
            $query->where('vice_mayor_id', $user->id);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Department::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
