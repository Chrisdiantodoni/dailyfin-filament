<div class="grid grid-cols-2 gap-6 p-4" x-data="denominationCalculator()">
    <!-- Header -->
    <div class="col-span-full">
        <h2 class="text-lg font-semibold ">Rincian Fisik Kas</h2>
    </div>
    <!-- Input hidden untuk menyimpan nilai yang sudah dibersihkan -->
    <input type="hidden" name="denomination.denom_100k" x-model="cleanValues.denom_100k" value="1000">
    <input type="hidden" name="denom_75k" x-model="cleanValues.denom_75k">
    <input type="hidden" name="denom_50k" x-model="cleanValues.denom_50k">
    <input type="hidden" name="denom_20k" x-model="cleanValues.denom_20k">
    <input type="hidden" name="denom_10k" x-model="cleanValues.denom_10k">
    <input type="hidden" name="denom_5k" x-model="cleanValues.denom_5k">
    <input type="hidden" name="denom_2k" x-model="cleanValues.denom_2k">
    <input type="hidden" name="denom_1k" x-model="cleanValues.denom_1k">
    <input type="hidden" name="denom_500" x-model="cleanValues.denom_500">
    <input type="hidden" name="denom_200" x-model="cleanValues.denom_200">
    <input type="hidden" name="denom_100" x-model="cleanValues.denom_100">
    <!-- Kolom Kiri -->
    <div class="grid grid-cols-1 gap-4">
        <div class="space-y-4">
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')"
                            x-model="denomination.denom_100k" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 100.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom100k" x-text="'Rp. ' + formatCurrency(totals['100k'])"">Rp.
                    0
                </div>
            </div>

            <!-- Denom 75k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_75k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <input type="hidden" wire:model="denom_75k" id="denom_75k">
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 75.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom75k">Rp. 0</div>
            </div>

            <!-- Denom 50k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_50k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 50.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom50k">Rp. 0</div>
            </div>

            <!-- Denom 20k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_20k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 20.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom20k">Rp. 0</div>
            </div>

            <!-- Denom 10k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_10k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 10.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom10k">Rp. 0</div>
            </div>
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_5k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 5.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom5k">Rp. 0</div>
            </div>
        </div>
        <!-- Denom 100k -->

        <!-- Denom 5k -->

    </div>
    <div class="grid grid-cols-1 gap-4">

        <!-- Kolom Kanan -->
        <div class="space-y-4">
            <!-- Denom 2k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" type="text"
                            wire:model="denom_2k" min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 2.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom2k">Rp. 0</div>
            </div>

            <!-- Denom 1k -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_1k"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 1.000</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom1k">Rp. 0</div>
            </div>

            <!-- Denom 500 -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_500"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 500</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom500">Rp. 0</div>
            </div>

            <!-- Denom 200 -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')" wire:model="denom_200"
                            min="0" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 200</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom200">Rp. 0</div>
            </div>

            <!-- Denom 100 -->
            <div class="grid grid-cols-12 gap-2 items-center">
                <div class="col-span-3">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-mask:dynamic="$money($input, ',')"
                            wire:model="denom_100" />
                    </x-filament::input.wrapper>
                </div>
                <div class="col-span-1 text-center">×</div>
                <div class="col-span-3">Rp. 100</div>
                <div class="col-span-1 text-center">=</div>
                <div class="col-span-4 font-medium" id="denom100">Rp. 0</div>
            </div>
        </div>
    </div>
    <!-- Total -->
    <div class="md:col-span-2 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
            <span class="text-lg font-semibold">Total:</span>
            <span class="text-xl font-bold" id="totalAmount">Rp. 0</span>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<script>
    function denominationCalculator() {
        return {
            // Inisialisasi nilai dari Livewire state
            denom100k: @entangle('denomination.denom_100k').defer,
            denom75k: @entangle('denomination.denom_75k').defer,
            denom50k: @entangle('denomination.denom_50k').defer,
            denom20k: @entangle('denomination.denom_20k').defer,
            denom10k: @entangle('denomination.denom_10k').defer,
            denom5k: @entangle('denomination.denom_5k').defer,
            denom2k: @entangle('denomination.denom_2k').defer,
            denom1k: @entangle('denomination.denom_1k').defer,
            denom500: @entangle('denomination.denom_500').defer,
            denom200: @entangle('denomination.denom_200').defer,
            denom100: @entangle('denomination.denom_100').defer,

            totals: {
                '100k': 0,
                '75k': 0,
                '50k': 0,
                '20k': 0,
                '10k': 0,
                '5k': 0,
                '2k': 0,
                '1k': 0,
                '500': 0,
                '200': 0,
                '100': 0
            },

            grandTotal: 0,

            init() {
                // Setel nilai default jika kosong
                if (!this.denom100k) this.denom100k = '0';
                if (!this.denom75k) this.denom75k = '0';
                if (!this.denom50k) this.denom50k = '0';
                if (!this.denom20k) this.denom20k = '0';
                if (!this.denom10k) this.denom10k = '0';
                if (!this.denom5k) this.denom5k = '0';
                if (!this.denom2k) this.denom2k = '0';
                if (!this.denom1k) this.denom1k = '0';
                if (!this.denom500) this.denom500 = '0';
                if (!this.denom200) this.denom200 = '0';
                if (!this.denom100) this.denom100 = '0';

                // Hitung total awal
                this.calculateAllTotals();
            },

            calculateAllTotals() {
                const denominations = [{
                        key: '100k',
                        value: 100000,
                        model: this.denom100k
                    },
                    {
                        key: '75k',
                        value: 75000,
                        model: this.denom75k
                    },
                    {
                        key: '50k',
                        value: 50000,
                        model: this.denom50k
                    },
                    {
                        key: '20k',
                        value: 20000,
                        model: this.denom20k
                    },
                    {
                        key: '10k',
                        value: 10000,
                        model: this.denom10k
                    },
                    {
                        key: '5k',
                        value: 5000,
                        model: this.denom5k
                    },
                    {
                        key: '2k',
                        value: 2000,
                        model: this.denom2k
                    },
                    {
                        key: '1k',
                        value: 1000,
                        model: this.denom1k
                    },
                    {
                        key: '500',
                        value: 500,
                        model: this.denom500
                    },
                    {
                        key: '200',
                        value: 200,
                        model: this.denom200
                    },
                    {
                        key: '100',
                        value: 100,
                        model: this.denom100
                    }
                ];

                this.grandTotal = 0;

                denominations.forEach(denom => {
                    const quantity = this.cleanCurrencyValue(denom.model);
                    this.totals[denom.key] = quantity * denom.value;
                    this.grandTotal += this.totals[denom.key];
                });
            },

            calculateTotal(nominal, key, value) {
                const quantity = this.cleanCurrencyValue(value);
                this.totals[key] = quantity * parseInt(nominal);

                // Update grand total
                this.calculateAllTotals();
            },

            updateLivewire(field, value) {
                // Membersihkan nilai sebelum mengirim ke Livewire
                const cleanValue = this.cleanCurrencyValue(value);

                // Update Livewire state
                this.$wire.set(`denomination.${field}`, cleanValue, false);
            },

            cleanCurrencyValue(value) {
                if (!value) return 0;
                return parseFloat(value.replace(/\./g, '')) || 0;
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID').format(amount);
            },

            $money(input, separator) {
                if (input === undefined || input === null) {
                    return '';
                }

                let value = input.replace(/[^\d]/g, '');

                if (value.length === 0) {
                    return '';
                }

                return value.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
            }
        };
    }
</script>
