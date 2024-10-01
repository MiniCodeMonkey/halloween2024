<?php

namespace App\Console\Commands;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;
use Illuminate\Console\Command;

class AudioAnimateCommand extends Command
{
    const LED_ON_THRESHOLD = 0.01;
    protected $signature = 'audio:animate {filename}';

    protected $description = 'Animates LED brightness in realtime based on an audio file';
    private Pin $led;

    public function handle(): void
    {
        $filename = $this->argument('filename');
        $jsonFilename = pathinfo($filename, PATHINFO_DIRNAME) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.json';

        $this->led = Pinout::pin(config('pinouts.led_eyes'));
        $this->led->makeOutput();

        if (file_exists($jsonFilename)) {
            $this->info('Animating ' . $filename);
            //Process::start('afplay ' . $filename);
            $data = json_decode(file_get_contents($jsonFilename), true);
            $this->animate($this->normalize($data));
        } else {
            $this->error('Audio file not analyzed: ' . $filename);
        }
    }

    private function animate(array $timingData): void
    {
        $startTime = microtime(true);
        foreach ($timingData as $entry) {
            $targetTime = $startTime + floatval($entry['time']);

            while (microtime(true) < $targetTime) {
                usleep(100);
            }

            //$this->getOutput()->write("\033[2J");

            if ($entry['on']) {
                //$this->error('AAAA');
                $this->led->turnOn();
            } else {
                //$this->info('AAAA');
                $this->led->turnOff();
            }
        }
    }

    private function normalize(array $data)
    {
        $data = collect($data);
        $max = $data->max('rms_linear');
        $data = $data->map(function ($entry) use ($max, $data) {
            $entry['percentage'] = round($entry['rms_linear'] / $max, 2);
            $entry['on'] = $entry['percentage'] > self::LED_ON_THRESHOLD;
            return $entry;
        });

        $data = $data->filter(function ($entry, $key) use ($data) {
            return $key === 0 || $entry['on'] !== $data[$key - 1]['on'];
        });

        return $data->toArray();
    }
}
