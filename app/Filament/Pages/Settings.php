<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Enums\RegionType;
use App\Models\AppSetting;
use App\Models\Region;
use App\Support\RegionResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?int $navigationSort = 90;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows(Permission::ManageSettings->value);
    }

    public function mount(): void
    {
        $focus = RegionResolver::focus();

        $this->form->fill([
            'focus_region_id' => $focus?->getKey(),
            'ai_daily_prompt_limit' => AppSetting::get('ai_daily_prompt_limit'),
            'ai_max_messages' => AppSetting::get('ai_max_messages'),
            'ai_system_prompt' => AppSetting::get('ai_system_prompt'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Kabupaten/Kota Fokus')
                    ->description('Aplikasi difokuskan pada satu kabupaten/kota. Wilayah lain tetap tersedia sebagai referensi.')
                    ->schema([
                        Select::make('focus_region_id')
                            ->label('Kabupaten/Kota')
                            ->options(fn (): array => Region::query()
                                ->ofType(RegionType::KabupatenKota)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Region $region): array => [
                                    $region->getKey() => $region->fullName(),
                                ])
                                ->all())
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->helperText('Daftar seluruh kabupaten/kota di Indonesia.'),
                    ]),

                Section::make('Batas AI Trip Planner')
                    ->description('Kosongkan untuk TIDAK membatasi. Pengaturan ini opsional selama pengujian.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ai_daily_prompt_limit')
                            ->label('Batas prompt per hari (per pengguna)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Tanpa batas')
                            ->helperText('Jumlah maksimum pesan yang boleh dikirim pengguna per hari.'),

                        TextInput::make('ai_max_messages')
                            ->label('Batas jumlah pesan tersimpan (per pengguna)')
                            ->numeric()
                            ->minValue(2)
                            ->placeholder('Tanpa batas')
                            ->helperText('Bila terlampaui, pesan paling lama dihapus otomatis.'),
                    ]),

                Section::make('Perilaku & Cakupan Jawaban AI')
                    ->description('Atur persona dan cakupan jawaban asisten. Kosongkan untuk memakai bawaan. Gunakan :region sebagai placeholder wilayah fokus.')
                    ->schema([
                        Textarea::make('ai_system_prompt')
                            ->label('Instruksi sistem (system prompt)')
                            ->rows(6)
                            ->placeholder('Kosongkan untuk memakai instruksi bawaan.')
                            ->helperText('Contoh: "Anda asisten wisata LokaNusa untuk :region. Jawab hanya seputar pariwisata. Bila ditanya hal di luar itu, arahkan kembali ke topik wisata."'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        AppSetting::setFocusRegion((int) $state['focus_region_id']);

        AppSetting::set('ai_daily_prompt_limit', $this->normalizeLimit($state['ai_daily_prompt_limit'] ?? null));
        AppSetting::set('ai_max_messages', $this->normalizeLimit($state['ai_max_messages'] ?? null));

        $prompt = trim((string) ($state['ai_system_prompt'] ?? ''));
        AppSetting::set('ai_system_prompt', $prompt !== '' ? $prompt : null);

        $focus = Region::query()->find($state['focus_region_id']);

        Notification::make()
            ->title('Pengaturan disimpan')
            ->body('Kabupaten/kota fokus: '.($focus?->fullName() ?? '-'))
            ->success()
            ->send();
    }

    /**
     * Normalkan nilai batas: kosong -> null (tanpa batas), selain itu string angka.
     */
    private function normalizeLimit(mixed $value): ?string
    {
        return filled($value) ? (string) (int) $value : null;
    }
}
