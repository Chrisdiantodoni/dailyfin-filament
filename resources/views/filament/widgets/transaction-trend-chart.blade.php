@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $isCollapsible = $this->isCollapsible();
@endphp

<x-filament-widgets::widget class="fi-wi-chart df-trend-widget">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
            class="relative"
        >
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($this->getType()),
                })"
                {{ (new ComponentAttributeBag)->color(ChartWidgetComponent::class, $color) }}
                @class(['opacity-25' => ! $this->hasTrendData()])
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>

            @unless ($this->hasTrendData())
                <div class="df-trend-empty">
                    <x-filament::icon icon="heroicon-m-chart-bar-square" class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Belum ada data trend pada periode ini
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Grafik akan muncul saat ada laporan Brankas, Mutasi Kas, atau Validasi Setoran sesuai filter.
                        </p>
                    </div>
                </div>
            @endunless
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
