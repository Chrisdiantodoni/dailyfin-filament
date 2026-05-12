<?php

namespace App\Filament\Pages;

use App\Models\Coordinator as CoordinatorModel;
use App\Models\CoordinatorCheck;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoordinatorDetail extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.pages.coordinator-detail';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'coordinator-detail/{id}';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-o-arrow-left')
                ->url('/app/coordinator'), // ← balik ke ListRecords
        ];
    }
    public function getTitle(): string
    {
        return 'Detail Koordinator';
    }
    public $record;
    public function mount(string $id): mixed
    {

        $coordinator = CoordinatorModel::find($id);
        $this->record = $coordinator;
        $this->infolist->record($coordinator);
        return $coordinator;
        // 2. The query parameter must be retrieved separately from the request.
    }

    public function approve($record)
    {
        try {
            DB::beginTransaction();

            $coordinator = CoordinatorModel::findOrFail($this->record->id); // ✅ pakai $this->record->id, bukan $record->id
            $coordinator->update([
                'status' => 'approve'
            ]);

            $approval_coordinator = new CoordinatorCheck();
            $approval_coordinator->coodinators_id = $this->record->id;
            $approval_coordinator->user_id = Auth::user()->id;
            $approval_coordinator->save();

            DB::commit();

            // ✅ Refresh $this->record supaya infolist ikut update
            $this->record = $coordinator->fresh();
            $this->infolist->record($this->record);



            // ✅ Refresh halaman Livewire
            $this->dispatch('$refresh');
        } catch (\Throwable $th) {
            DB::rollBack(); // ✅ jangan lupa rollback kalau error
            Log::error($th->getMessage());

            \Filament\Notifications\Notification::make()
                ->title('Gagal mengkonfirmasi')
                ->body($th->getMessage())
                ->danger()
                ->send();
        }
    }

    public function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->record($this->record)->schema([
            Grid::make([
                'default' => 1,
                'lg' => 2,
            ])->schema([
                Section::make('Informasi Umum')->schema([
                    TextEntry::make('date_published')
                        ->label('Tanggal')->date("d M Y"),
                    TextEntry::make('dealers.dealer_name')
                        ->label('Dealer'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'approve' => 'success',
                            'request' => 'warning',
                            'reject'  => 'danger',
                            default   => 'gray',
                        })->formatStateUsing(fn($state) => match ($state) {
                            'approve' => 'Disetujui',
                            'request' => 'Menunggu',
                            'reject'  => 'Ditolak',
                            default   => ucfirst($state),
                        }),



                ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),


                Section::make('Saldo Selisih')->schema([
                    TextEntry::make('start_balance_cashier')
                        ->getStateUsing(fn($record) => $record->start_balance_cashier - $record->start_balance_mutates)
                        ->money('Rp.', locale: 'ID')
                        ->label('Saldo Awal')
                        ->color(fn($state) => $state < 0 ? 'danger' : null), // merah jika minus

                    TextEntry::make('end_balance_cashier')
                        ->getStateUsing(fn($record) => $record->end_balance_cashier - $record->end_balance_mutates)
                        ->money('Rp.', locale: 'ID')
                        ->label('Saldo Akhir')
                        ->color(fn($state) => $state < 0 ? 'danger' : null), // merah jika minus

                    TextEntry::make('invoice_cashier')
                        ->getStateUsing(fn($record) => $record->invoice_cashier - $record->invoice_mutates)
                        ->money('Rp.', locale: 'ID')
                        ->label('Kas Gantung')
                        ->color(fn($state) => $state < 0 ? 'danger' : null), // merah jika minus
                ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ])->columnSpanFull(),
            Actions::make([
                Action::make('confirm')
                    ->label("Konfirmasi")
                    ->color('info')
                    ->modalHeading('Konfirmasi')
                    ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                    ->modalSubmitActionLabel('Ya, Konfirmasi')
                    ->modalCancelActionLabel('Batal')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Logic untuk konfirmasi
                        $this->approve($record);
                    })
                    ->hidden(fn($record) => $this->record?->status != 'request' && (getRole() != 'IT' || getRole() != 'Finance Operation')),


            ])->extraAttributes([
                'class' => 'flex gap-2 bg-transparent',
            ])->columnSpanFull(),
            Grid::make([
                'default' => 1,
                'lg' => 2,
            ])->schema([
                Section::make('Saldo Kasir')->schema([
                    TextEntry::make('start_balance_cashier')->money('Rp.', locale: 'ID')
                        ->label('Saldo Awal'),
                    TextEntry::make('end_balance_cashier')->money('Rp.', locale: 'ID')
                        ->label('Saldo Akhir'),

                    TextEntry::make('invoice_cashier')->money('Rp.', locale: 'ID')
                        ->label('Kas Gantung'),

                ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Saldo GL')->schema([
                    TextEntry::make('start_balance_mutates')->money('Rp.', locale: 'ID')
                        ->label('Saldo Awal'),
                    TextEntry::make('end_balance_mutates')->money('Rp.', locale: 'ID')
                        ->label('Saldo Akhir'),

                    TextEntry::make('invoice_mutates')->money('Rp.', locale: 'ID')
                        ->label('Kas Gantung'),

                ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ])->columnSpanFull()

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\CoordinatorCheck::query()->where('coodinators_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
