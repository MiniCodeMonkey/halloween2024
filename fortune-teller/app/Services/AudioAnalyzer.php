<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Process\Process;

class AudioAnalyzer
{
    public function analyze(string $audioFilename): void
    {
        if (!file_exists($audioFilename)) {
            throw new FileNotFoundException("File not found: $audioFilename");
        }

        $jsonFilename = pathinfo($audioFilename, PATHINFO_DIRNAME) . '/' . pathinfo($audioFilename, PATHINFO_FILENAME) . '.json';

        if (!file_exists($jsonFilename)) {
            $this->processAudioFile($audioFilename, $jsonFilename);
        }
    }

    private function processAudioFile(string $audioFilename, string $jsonFilename): void
    {
        $analyzeCommand = [
            'ffmpeg',
            '-i', $audioFilename,
            '-af', 'astats=metadata=1:reset=1,ametadata=print:key=lavfi.astats.Overall.RMS_level:file=-',
            '-f', 'null',
            '-'
        ];

        $process = new Process($analyzeCommand);
        $process->setTimeout(null);
        $process->mustRun();

        $volumeData = $this->parseOutput($process->getOutput());

        file_put_contents($jsonFilename, json_encode($volumeData, JSON_PRETTY_PRINT));
    }

    private function parseOutput(string $output): array
    {
        $volumeData = [];

        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/pts_time:(\d+\.?\d*)/', $line, $matches)) {
                $time = $matches[1];
            }

            if (preg_match('/lavfi\.astats\.Overall\.RMS_level=([-]?\d+\.?\d*)/', $line, $matches)) {
                $rmsDb = floatval($matches[1]);
                $rmsLinear = pow(10, $rmsDb / 20);
                $volumeData[] = [
                    'time' => $time,
                    'rms_db' => $rmsDb,
                    'rms_linear' => $rmsLinear
                ];
            }
        }

        return $volumeData;
    }
}
