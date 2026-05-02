<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Masking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kullanıcılar';

    protected static ?string $modelLabel = 'kullanıcı';

    protected static ?string $pluralModelLabel = 'kullanıcılar';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kullanıcı')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ad soyad')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-posta')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('E-posta doğrulandı'),
                        Forms\Components\TextInput::make('password')
                            ->label('Şifre')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->label('Rol')
                            ->options(function (): array {
                                $user = auth()->user();
                                if ($user?->isAdmin()) {
                                    return collect(UserRole::cases())->mapWithKeys(
                                        fn (UserRole $role): array => [$role->value => (string) $role->getLabel()],
                                    )->all();
                                }

                                return [UserRole::Staff->value => (string) UserRole::Staff->getLabel()];
                            })
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('department_id')
                            ->label('Müdürlük')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-posta')
                    ->formatStateUsing(function (?string $state): string {
                        $user = auth()->user();
                        if ($user?->isAdmin()) {
                            return (string) $state;
                        }

                        return Masking::email($state);
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn ($state): string => $state instanceof UserRole ? (string) ($state->getLabel() ?? $state->value) : (string) $state)
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Müdürlük')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Doğrulama')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var User $record */
        return [
            'E-posta' => $record->email,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', User::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isViceMayor()) {
            return $query
                ->whereIn('department_id', $user->managedDepartmentIds())
                ->where('role', '!=', UserRole::Admin->value);
        }
        if ($user->isManager() && $user->department_id) {
            return $query
                ->where('department_id', $user->department_id)
                ->where('role', UserRole::Staff->value);
        }

        return $query->whereKey($user->id);
    }
}
