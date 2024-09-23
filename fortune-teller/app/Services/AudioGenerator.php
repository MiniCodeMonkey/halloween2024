<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\Polly\PollyClient;
use RuntimeException;
use Symfony\Component\Process\Process;

class AudioGenerator
{
    public function __construct(private PollyClient $pollyClient)
    {
    }

    public function say(string $message): bool
    {
        $message = trim($message);
        $messageKey = md5($message);

        $filename = storage_path("app/messages/$messageKey.mp3");
        if (!file_exists($filename)) {
            try {
                $result = $this->pollyClient->synthesizeSpeech([
                    'OutputFormat' => 'mp3',
                    'Text' => $message,
                    'TextType' => str_starts_with($message, '<speak>') ? 'ssml' : 'text',
                    'VoiceId' => 'Sofie',
                    'Engine' => 'neural',
                ]);

                file_put_contents($filename, $result['AudioStream']->getContents());
            } catch (AwsException $e) {
                info("Error in text-to-speech: " . $e->getMessage());
                return false;
            }
        }

        $this->play($filename);

        return true;
    }

    function play($filename): void
    {
        info('Playing ' . $filename);

        $filename = $this->transposeAudio($filename);

        if (PHP_OS === 'Linux') {
            $process = new Process(['ffplay', '-v', '0', '-nodisp', '-autoexit', $filename]);
        } else if (ShellCommandChecker::doesCommandExist('ffplay')) {
            $process = new Process(['afplay', $filename]);
        }

        if (!isset($process)) {
            throw new RuntimeException('No audio player found');
        }

        $process->mustRun();
    }

    private function transposeAudio(string $filename): string
    {
        $tmpFilename = storage_path('tmp.wav');
        $process = new Process([
            'sox', $filename, $tmpFilename,
            'channels', '1',
            'pitch', '-150',
            'reverb', '50', '50', '50',
            'contrast', '50',
            'treble', '+10', '100',
            'echos', '0.8', '0.7', '50', '0.25', '60', '0.3'
        ]);

        $process->run();

        return $process->isSuccessful()
            ? $tmpFilename
            : $filename;
    }
}
