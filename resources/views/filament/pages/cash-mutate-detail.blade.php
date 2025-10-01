<x-filament-panels::page>
    {{-- Page content --}}
    <meta name="user-id" content="{{ auth()->user()->id }}">
    {{ $this->infolist }}
    <x-filament::section>
        <x-slot name="heading">
            Riwayat Persetujuan
        </x-slot>

        {{ $this->table }}

    </x-filament::section>
    @filamentScripts

    <script>
        window.userId = @js(auth()->id())
    </script>
</x-filament-panels::page>
