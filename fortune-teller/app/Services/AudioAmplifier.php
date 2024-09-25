<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AudioAmplifier
{
    public function amplifyAudioFile(string $inputFile, string $outputFile, int $gainIncrease = 20): void
    {
        $process = new Process([
            'ffmpeg',
            '-i', $inputFile,
            '-filter:a', "volume={$gainIncrease}dB",
            '-c:a', 'libmp3lame',
            '-b:a', '128k',
            $outputFile
        ]);
        $process->setTimeout(30);
        $process->mustRun();
    }
}
