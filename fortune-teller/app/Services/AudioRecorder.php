<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AudioRecorder
{
    public function record(int $maxDurationSeconds = 10): string
    {
        $filename = storage_path('recording_raw.wav');

        if (PHP_OS === 'Linux') {
            $process = new Process(['arecord', '-D', 'plughw:CARD=v2,DEV=0', '-f', 'S16_LE', '-c1', '-r48000', '-d', $maxDurationSeconds, $filename]);
        } else {
            $process = new Process(['rec', '-q', '-t', 'wav', '-r', '48000', '-c', '1', '-b', '16', $filename, 'gain', '10', 'silence', '1', '0.1', '3%', '1', '1.0', '3%', 'trim', '0', $maxDurationSeconds]);
        }

        $process->mustRun();

        $resampledFilename = storage_path('recording_resampled.wav');
        $this->resample($filename, $resampledFilename);

        return $resampledFilename;
    }

    private function resample($inputFilename, $outputFilename): void
    {
        (new Process(['sox', $inputFilename, '-r', '16000', $outputFilename]))->mustRun();
    }
}
