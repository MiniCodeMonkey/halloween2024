<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AudioAmplifier
{
    public function amplifyAudioFile(string $filename, int $gainIncrease = 18): void
    {
        $amplifiedFilename = $this->getAmplifiedFilename($filename);

        $process = new Process([
            'ffmpeg',
            '-i', $filename,
            '-filter:a', "volume={$gainIncrease}dB",
            '-c:a', 'libmp3lame',
            '-b:a', '128k',
            $amplifiedFilename
        ]);
        $process->setTimeout(30);
        $process->mustRun();

        @unlink($filename);
        rename($amplifiedFilename, $filename);
    }

    private function getAmplifiedFilename(string $filename): string
    {
        return pathinfo($filename, PATHINFO_DIRNAME) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_amplified.' . pathinfo($filename, PATHINFO_EXTENSION);
    }
}
