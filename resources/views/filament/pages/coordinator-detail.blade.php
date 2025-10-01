<x-filament-panels::page>
    {{-- Page content --}}
    <meta name="user-id" content="{{ auth()->user()->id }}">

    {{ $this->infolist }}
    <x-filament::section>
        <x-slot name="heading">
            Riwayat
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
    <script>
        window.userId = @js(auth()->id())
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Ambil base URL dari browser
            const baseUrl = window.location.origin; // contoh: http://127.0.0.1:8000
            const path = '/app/coordinator'; // path sidebar yang ingin diaktifkan

            // Cari <a> dengan href sesuai
            const link = document.querySelector(`.fi-sidebar-item a[href="${baseUrl}${path}"]`);

            if (link) {
                const li = link.closest('li.fi-sidebar-item');
                if (li) {
                    li.classList.add('fi-active');
                    console.log('Sidebar item aktif:', li);
                }
            }
        });
    </script>



</x-filament-panels::page>
