<?php

namespace App\Services;

use ArdaGnsrn\ElevenLabs\ElevenLabs;
use Illuminate\Process\InvokedProcessPool;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Throwable;

class AudioGenerator
{
    const VOICE_ID = '7NsaqHdLuKNFvEfjpUno';
    private ?InvokedProcessPool $playPool = null;

    public function __construct(private ElevenLabs $elevenLabs, private AudioAmplifier $audioAmplifier, private AudioAnalyzer $audioAnalyzer)
    {
    }

    public function say(string $message, bool $playAfterGenerating = true, bool $playAsync = false): bool
    {
        $message = trim($message);
        info(($playAfterGenerating ? 'Saying' : 'Caching') . ': ' . $message);

        $messageKey = $this->getMessageKey($message);
        $audioFilename = storage_path("app/messages/$messageKey.mp3");

        if (!file_exists($audioFilename)) {
            info("\t" . 'Generating audio file: ' . $audioFilename);
            $this->convertTextToSpeech($message, $audioFilename);

            try {
                $this->audioAmplifier->amplifyAudioFile($audioFilename);
            } catch (Throwable $e) {
                info("\t" . 'Failed to amplify audio: ' . $e->getMessage());
            }
        } else {
            info("\t" . 'Using cached audio file');
        }

        $this->audioAnalyzer->analyze($audioFilename);

        if ($playAfterGenerating) {
            $this->play($audioFilename, $playAsync);
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

        if ($this->playPool) {
            info('Stopping pool');
            $this->playPool->stop();
        }

        if (PHP_OS === 'Linux') {
            $playProcess = ['ffplay', '-v', '0', '-nodisp', '-autoexit', $filename];
        } else {
            $playProcess = ['afplay', $filename];
        }

        $this->playPool = Process::pool(function (Pool $pool) use ($filename, $playProcess) {
            $pool->path(base_path())->command($playProcess);
            //$pool->path(base_path())->command(['php', 'artisan', 'audio:animate', $filename]);
        })->start();

        if (!$async) {
            $this->playPool->wait();
        }
    }

    private function getMessageKey(string $message): string
    {
        return implode('_', ['el', self::VOICE_ID, md5($message)]);
    }

    private function convertTextToSpeech(string $message, string $audioFilename): void
    {
        $response = $this->elevenLabs->textToSpeech(self::VOICE_ID, $message, 'eleven_multilingual_v2', [
            'stability' => 0.30,
            'similarity_boost' => 0.75,
            'style' => 0.5,
            'use_speaker_boost' => false
        ]);

        file_put_contents($audioFilename, $response->getResponse()->getBody()->getContents());
    }
}
