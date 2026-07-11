<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ClassifierTrainingSampleResource\Pages;
use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Services\MudurlukClassifierService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassifierTrainingSampleResource extends Resource
{
    protected static ?string $model = ClassifierTrainingSample::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Model eğitim örnekleri';

    protected static ?string $modelLabel = 'eğitim örneği';

    protected static ?string $pluralModelLabel = 'eğitim örnekleri';

    protected static ?string $navigationGroup = 'Yapay Zeka';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Örnek metin')
                    ->description('Vatandaş şikâyetine benzer kısa bir cümle girin. Model bu metni ilgili müdürlükle eşleştirir.')
                    ->schema([
                        Forms\Components\Textarea::make('sikayet_metni')
                            ->label('Şikâyet / talep metni')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('department_id')
                            ->label('Doğru müdürlük')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Eğitimde kullan')
                            ->default(true)
                            ->helperText('Kapalı örnekler yeniden eğitimde dikkate alınmaz.'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Not')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sikayet_metni')
                    ->label('Metin')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Müdürlük')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mudurluk_slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Ekleyen')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Güncellendi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Eğitimde kullan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassifierTrainingSamples::route('/'),
            'create' => Pages\CreateClassifierTrainingSample::route('/create'),
            'edit' => Pages\EditClassifierTrainingSample::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department:id,name', 'creator:id,name']);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null
            && in_array($user->role, [UserRole::Admin, UserRole::ViceMayor], true);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function mutateBeforeCreate(array $data): array
    {
        return static::applySlug($data);
    }

    public static function mutateBeforeSave(array $data): array
    {
        return static::applySlug($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function applySlug(array $data): array
    {
        if (! empty($data['department_id'])) {
            $department = Department::query()->find($data['department_id']);
            if ($department) {
                $data['mudurluk_slug'] = app(MudurlukClassifierService::class)->slugify($department->name);
            }
        }

        return $data;
    }
}
