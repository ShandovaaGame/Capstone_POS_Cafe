<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\StaffSession;
use App\Models\User;
use App\Services\StaffSessionService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class StaffSessionDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'staff-session-detail/{type}/{session}';

    protected static ?string $title = 'Detail Sesi Staff';

    public StaffSession $sessionRecord;

    public User $staff;

    public string $type;

    public int $orderCount = 0;

    public function mount(string $type, string $session): void
    {
        if (! in_array($type, ['cashier', 'kitchen'])) {
            abort(404);
        }

        $this->type = $type;
        $this->sessionRecord = StaffSession::with('user')->findOrFail($session);
        $this->staff = $this->sessionRecord->user;
        $this->orderCount = app(StaffSessionService::class)->getOrderCount($this->sessionRecord);
    }

    public function getTitle(): string
    {
        return "Detail Sesi {$this->getTypeLabel()} — {$this->staff->name}";
    }

    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'cashier' => 'Kasir',
            'kitchen' => 'Dapur',
            default   => $this->type,
        };
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Sesi')
                ->schema([
                    TextEntry::make('nama')
                        ->label('Nama Staff')
                        ->state($this->staff->name),
                    TextEntry::make('email')
                        ->label('Email')
                        ->state($this->staff->email),
                    TextEntry::make('role')
                        ->label('Role')
                        ->state($this->getTypeLabel()),
                    TextEntry::make('masuk')
                        ->label('Waktu Masuk')
                        ->state($this->sessionRecord->started_at->format('d M Y, H:i')),
                    TextEntry::make('keluar')
                        ->label('Waktu Keluar')
                        ->state($this->sessionRecord->ended_at?->format('d M Y, H:i') ?? 'Masih Aktif'),
                    TextEntry::make('pesanan')
                        ->label('Jumlah Pesanan')
                        ->state($this->orderCount),
                ])
                ->columns(3),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $session = $this->sessionRecord;
        $endedAt = $session->ended_at ?? now();

        $baseQuery = match ($session->type) {
            'cashier' => Order::with('cafeTable')
                ->where('cashier_id', $session->user_id)
                ->whereBetween('created_at', [$session->started_at, $endedAt]),
            default => Order::with('cafeTable')
                ->where('processed_by', $session->user_id)
                ->where('status', Order::STATUS_SELESAI)
                ->whereBetween('created_at', [$session->started_at, $endedAt]),
        };

        return $table
            ->query($baseQuery->latest())
            ->columns([
                TextColumn::make('order_code')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('cafeTable.table_number')
                    ->label('Meja')
                    ->default('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'diproses'  => 'info',
                        'selesai'   => 'success',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'Pending',
                        'diproses'  => 'Diproses',
                        'selesai'   => 'Selesai',
                        default     => $state,
                    }),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
