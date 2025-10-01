@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\View\Components\BadgeComponent;
    use Illuminate\View\ComponentAttributeBag;

    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
@endphp

<div class="fi-no-database">
    <x-filament::modal :alignment="$hasNotifications ? null : Alignment::Center" close-button :description="$hasNotifications ? null : __('filament-notifications::database.modal.empty.description')" :heading="$hasNotifications ? null : __('filament-notifications::database.modal.empty.heading')" :icon="$hasNotifications ? null : \Filament\Support\Icons\Heroicon::OutlinedBellSlash"
        :icon-alias="$hasNotifications
            ? null
            : \Filament\Notifications\View\NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE" :icon-color="$hasNotifications ? null : 'gray'" id="database-notifications" slide-over :sticky-header="$hasNotifications" width="md"
        :attributes="new \Illuminate\View\ComponentAttributeBag([
            'wire:poll.' . $pollingInterval => $pollingInterval ? '' : false,
        ])">
        @if ($trigger = $this->getTrigger())
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>
        @endif

        @if ($hasNotifications)
            <x-slot name="header">
                <div>
                    <h2 class="fi-modal-heading">
                        {{ __('filament-notifications::database.modal.heading') }}

                        @if ($unreadNotificationsCount)
                            <x-filament::badge color="primary" class="fi-size-xs">
                                {{ $unreadNotificationsCount }}
                            </x-filament::badge>
                        @endif

                    </h2>

                    <div class="fi-ac">
                        @if ($unreadNotificationsCount && $this->markAllNotificationsAsReadAction?->isVisible())
                            {{ $this->markAllNotificationsAsReadAction }}
                        @endif

                        @if ($this->clearNotificationsAction?->isVisible())
                            {{ $this->clearNotificationsAction }}
                        @endif
                    </div>
                </div>
            </x-slot>

            @foreach ($notifications as $notification)
                <div @class([
                    'fi-no-notification-unread-ctn' => $notification->unread(),
                ])>
                    {{ $this->getNotification($notification)->inline() }}
                </div>
            @endforeach

            @if ($broadcastChannel = $this->getBroadcastChannel())
                @script
                    <script>
                        // Debug info
                        console.log('🔍 Broadcast channel:', @js($broadcastChannel));
                        console.log('🔍 Echo available:', typeof window.Echo !== 'undefined');

                        function setupEchoListener() {
                            try {
                                window.Echo.private(@js($broadcastChannel))
                                    .listen('.database-notifications.sent', (e) => {
                                        console.log('🎯 NOTIFIKASI DITERIMA:', e);

                                        // 1. Refresh Livewire
                                        setTimeout(() => {
                                            $wire.call('$refresh');
                                            console.log('✅ Livewire refreshed');
                                        }, 500);
                                    })
                                    .error((error) => {
                                        console.error('❌ Echo error:', error);
                                    });

                                console.log('✅ Echo listener setup successfully');
                            } catch (error) {
                                console.error('❌ Error setting up listener:', error);
                            }
                        }

                        // Jika Echo sudah loaded
                        if (window.Echo) {
                            console.log('✅ Echo is ready, setting up listener...');
                            setupEchoListener();
                        } else {
                            // Jika Echo belum loaded, tunggu event
                            console.log('⏳ Echo not ready, waiting for EchoLoaded event...');
                            window.addEventListener('EchoLoaded', () => {
                                console.log('✅ EchoLoaded event received, setting up listener...');
                                setupEchoListener();
                            });
                        }

                        // Trigger event jika Echo sudah loaded
                        if (window.Echo) {
                            window.dispatchEvent(new CustomEvent('EchoLoaded'));
                        }
                    </script>
                @endscript
            @endif

            @if ($isPaginated)
                <x-slot name="footer">
                    <x-filament::pagination :paginator="$notifications" />
                </x-slot>
            @endif
        @endif

    </x-filament::modal>
</div>
