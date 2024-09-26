<?php

namespace App\Services;

use ArdaGnsrn\ElevenLabs\ElevenLabs;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AudioGenerator
{
    private ?Process $playProcess = null;

    public function __construct(private ElevenLabs $elevenLabs, private AudioAmplifier $audioAmplifier)
    {
    }

    public function say(string $message, bool $playAfterGenerating = true, bool $playAsync = false): bool
    {
        $message = trim($message);
        $voiceId = '7NsaqHdLuKNFvEfjpUno';
        $messageKey = implode('_', ['el', $voiceId, md5($message)]);
        $filename = storage_path("app/messages/$messageKey.mp3");

        info(($playAfterGenerating ? 'Saying' : 'Caching') . ': ' . $message);

        if (!file_exists($filename)) {
            info("\t" . 'Generating audio file: ' . $filename);
            $response = $this->elevenLabs->textToSpeech($voiceId, $message, 'eleven_multilingual_v2', [
                'stability' => 0.30,
                'similarity_boost' => 0.75,
                'style' => 0.5,
                'use_speaker_boost' => false
            ]);

            file_put_contents($filename, $response->getResponse()->getBody()->getContents());

            $amplifiedFilename = storage_path("app/messages/{$messageKey}_amplified.mp3");

            try {
                $this->audioAmplifier->amplifyAudioFile($filename, $amplifiedFilename);
                @unlink($filename);
                rename($amplifiedFilename, $filename);
            } catch (Throwable $e) {
                info("\t" . 'Failed to amplify audio: ' . $e->getMessage());
            }
        } else {
            info("\t" . 'Using cached audio file');
        }

        if ($playAfterGenerating) {
            $this->play($filename, $playAsync);
        }

        return true;
    }

    public function sayAsync(string $message): bool
    {
        return $this->say($message, playAsync: true);
    }

    public function cache(string $message): bool
    {
        return $this->say($message, playAfterGenerating: false);
    }

    private function play(string $filename, bool $async = false): void
    {
        info('Playing ' . $filename);

        if ($this->playProcess) {
            $this->playProcess->stop();
        }

        if (PHP_OS === 'Linux') {
            $this->playProcess = new Process(['ffplay', '-v', '0', '-nodisp', '-autoexit', $filename]);
        } else {
            $this->playProcess = new Process(['afplay', $filename]);
        }

        if (!isset($this->playProcess)) {
            throw new RuntimeException('No audio player found');
        }

        if ($async) {
            $this->playProcess->start();
        } else {
            $this->playProcess->mustRun();
        }
    }
}
