<?php

namespace App\Providers;

use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        RichEditor::configureUsing(function ($editor) {
            $editor->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                ['table'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                ['undo', 'redo'],
            ]);
            $editor->disableToolbarButtons([
                //'blockquote',
                //'strike',
            ]);
        });
        FilamentTimezone::set('Asia/Jakarta');
        Blade::directive('formatNumber', function ($expression) {
            return "<?php echo 'Rp.' . number_format($expression, 0, ',', '.'); ?>";
        });
        // FilamentAsset::register([
        //     Js::make('bootstrap', __DIR__ . '/../../resources/js/bootstrap.js'),
        // ]);
        // FilamentAsset::register([
        //     Css::make('app', __DIR__ . '/../../resources/css/app.css'),
        // ]);
        Blade::directive('canUser', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->canUser({$permission})): ?>";
        });

        Blade::directive('endCanUser', function () {
            return '<?php endif; ?>';
        });
        Blade::directive('formatDate', function ($expression) {
            return "{{date('d M Y', strtotime($expression))}}";
        });
    }
}
