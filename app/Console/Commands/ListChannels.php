<?php

// app/Console/Commands/ListChannels.php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListChannels extends Command
{
    protected $signature = 'broadcast:channels';
    protected $description = 'List all broadcast channels';

    public function handle()
    {
        $channelsFile = base_path('routes/channels.php');
        $contents = file_get_contents($channelsFile);

        // Cari semua Broadcast::channel('channelName', ...
        preg_match_all("/Broadcast::channel\(\s*'([^']+)'/", $contents, $matches);
        $channels = $matches[1] ?? [];

        if (empty($channels)) {
            $this->info("No channels found.");
        } else {
            $this->info("Channels found:");
            foreach ($channels as $c) {
                $this->line($c);
            }
        }
    }
}
