<?php

namespace App\Console\Commands;

use App\Services\Led;
use Illuminate\Console\Command;

class AudioAnimateCommand extends Command
{
    protected $signature = 'audio:animate {filename}';

    protected $description = 'Animates LED brightness in realtime based on an audio file';
    private Led $led;

    public function handle(): void
    {
        $filename = $this->argument('filename');
        $jsonFilename = pathinfo($filename, PATHINFO_DIRNAME) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.json';

        $this->led = new Led();
        $this->led->enable();

        if (file_exists($jsonFilename)) {
            $this->info('Animating ' . $filename);
            $this->animate(json_decode(file_get_contents($jsonFilename), true));
        } else {
            $this->error('Audio file not analyzed: ' . $filename);
        }

        $this->led->disable();
    }

    private function animate(array $timingData): void
    {
        $startTime = microtime(true);
        foreach ($timingData as $entry) {
            $targetTime = $startTime + floatval($entry['time']);

            // Wait until it's time to set the brightness
            while (microtime(true) < $targetTime) {
                // Busy-wait or you could use usleep() for more efficient waiting
                // usleep(100); // Uncomment this line for more efficient CPU usage
            }

            // Set the brightness
            $this->setBrightness($entry['rms_linear']);
        }
    }

    private function setBrightness(mixed $rms_linear)
    {
        $this->led->setBrightness($rms_linear * 100);
    }
}
