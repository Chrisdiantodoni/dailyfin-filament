<x-filament-widgets::widget class="df-dashboard-table-widget">
    <x-filament::section :heading="$this->getHeading()">
        @php $transactions = $this->getTransactions(); @endphp

        <div class="df-dashboard-table-content">
            @if($transactions->isEmpty())
                <div class="df-dashboard-table-empty text-sm text-gray-500 dark:text-gray-400 text-center">
                    <x-filament::icon icon="heroicon-m-inbox" class="h-8 w-8 mx-auto mb-2 text-gray-400" />
                    Tidak ada transaksi hari ini.
                </div>
            @else
                <div class="df-dashboard-mobile-list">
                    @foreach($transactions as $tx)
                        <div class="df-dashboard-mobile-row">
                            <div class="df-dashboard-mobile-row-main">
                                <x-filament::badge :color="$this->getColor($tx->type)">
                                    {{ $tx->type }}
                                </x-filament::badge>
                                <x-filament::badge :color="$this->getStatusColor($tx->status)">
                                    {{ $this->getStatusLabel($tx->status) }}
                                </x-filament::badge>
                            </div>
                            <div class="df-dashboard-mobile-row-main">
                                <span class="df-dashboard-mobile-amount">
                                    Rp {{ number_format($tx->amount ?? 0, 0, ',', '.') }}
                                </span>
                                <span class="df-dashboard-mobile-time">
                                    {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }}
                                </span>
                            </div>
                            <div class="df-dashboard-mobile-row-meta">
                                <span>{{ \Carbon\Carbon::parse($tx->date_published)->format('d M Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="df-dashboard-table-scroll overflow-x-auto">
                    <table class="fi-table w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">Jenis</th>
                                <th class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">Tanggal</th>
                                <th class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">Nominal</th>
                                <th class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">Status</th>
                                <th class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($transactions as $tx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-3 py-2">
                                        <x-filament::badge :color="$this->getColor($tx->type)">
                                            {{ $tx->type }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($tx->date_published)->format('d M Y') }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200 font-medium">
                                        Rp {{ number_format($tx->amount ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <x-filament::badge :color="$this->getStatusColor($tx->status)">
                                            {{ $this->getStatusLabel($tx->status) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
