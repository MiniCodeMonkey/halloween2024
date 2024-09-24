<?php

namespace App\Services;

use ArdaGnsrn\ElevenLabs\ElevenLabs;
use RuntimeException;
use Symfony\Component\Process\Process;

class AudioGenerator
{
    public function __construct(private ElevenLabs $elevenLabs)
    {
    }

    public function say(string $message): bool
    {
        $message = trim($message);
        $voiceId = '7NsaqHdLuKNFvEfjpUno';

        $messageKey = implode('_', ['el', $voiceId, md5($message)]);

        $filename = storage_path("app/messages/$messageKey.mp3");
        if (!file_exists($filename)) {
            $response = $this->elevenLabs->textToSpeech($voiceId, $message, 'eleven_multilingual_v2', [
                'stability' => 0.30,
                'similarity_boost' => 0.75,
                'style' => 0.5,
                'use_speaker_boost' => false
            ]);

            file_put_contents($filename, $response->getResponse()->getBody()->getContents());
        }

        $this->play($filename);

        return true;
    }

    function play($filename): void
    {
        info('Playing ' . $filename);

        if (PHP_OS === 'Linux') {
            $process = new Process(['ffplay', '-v', '0', '-nodisp', '-autoexit', $filename]);
        } else {
            $process = new Process(['afplay', $filename]);
        }

        if (!isset($process)) {
            throw new RuntimeException('No audio player found');
        }

        $process->mustRun();
    }
}
