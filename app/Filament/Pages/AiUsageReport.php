<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\AiUsage;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Laporan pemakaian token AI per pengguna (khusus admin).
 */
class AiUsageReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.ai-usage-report';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Penggunaan Token AI';

    protected static ?string $title = 'Penggunaan Token AI';

    protected static ?int $navigationSort = 91;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function canAccess(): bool
    {
        return Gate::allows(Permission::ManageAiKnowledge->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->select('users.*')
                    ->addSelect([
                        'ai_total_tokens' => AiUsage::query()
                            ->selectRaw('coalesce(sum(total_tokens), 0)')
                            ->whereColumn('user_id', 'users.id'),
                        'ai_today_tokens' => AiUsage::query()
                            ->selectRaw('coalesce(sum(total_tokens), 0)')
                            ->whereColumn('user_id', 'users.id')
                            ->where('created_at', '>=', Carbon::today()),
                        'ai_requests' => AiUsage::query()
                            ->selectRaw('count(*)')
                            ->whereColumn('user_id', 'users.id'),
                        'ai_last_used_at' => AiUsage::query()
                            ->selectRaw('max(created_at)')
                            ->whereColumn('user_id', 'users.id'),
                    ])
                    ->whereExists(fn ($query) => $query
                        ->from('ai_usages')
                        ->whereColumn('ai_usages.user_id', 'users.id'))
            )
            ->defaultSort('ai_total_tokens', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Pengguna')
                    ->searchable()
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? '-')
                    ->color(fn ($state): string => $state?->color() ?? 'gray'),

                TextColumn::make('ai_today_tokens')
                    ->label('Token Hari Ini')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('ai_total_tokens')
                    ->label('Total Token')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('ai_requests')
                    ->label('Permintaan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('ai_last_used_at')
                    ->label('Terakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }
}
