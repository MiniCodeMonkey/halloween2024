<?php

namespace App\Services;

use Google\ApiCore\OperationResponse;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
use Illuminate\Support\Facades\App;
use RuntimeException;

class SpeechToTextProcessor
{
    private SpeechClient $speechClient;
    private ?OperationResponse $operation = null;

    public function __construct(SpeechClient $speechClient)
    {
        $this->speechClient = $speechClient;
    }

    public function transcribe($filename): void
    {
        $languageCode = App::isLocale('da') ? 'da-DK' : 'en-US';

        info('Transcribing with language code ' . $languageCode);

        $content = file_get_contents($filename);
        $audio = (new RecognitionAudio())->setContent($content);

        $config = (new RecognitionConfig())
            ->setEncoding(AudioEncoding::LINEAR16)
            ->setSampleRateHertz(16000)
            ->setLanguageCode($languageCode)
            ->setAudioChannelCount(1);

        $this->operation = $this->speechClient->longRunningRecognize($config, $audio);
    }

    public function getTranscription(): string
    {
        if (!$this->operation) {
            throw new RuntimeException('Please call transcribe() first');
        }

        $this->operation->pollUntilComplete();

        if (!$this->operation->operationSucceeded()) {
            throw new RuntimeException($this->operation->getError());
        }

        $response = $this->operation->getResult();
        $transcription = '';
        foreach ($response->getResults() as $result) {
            $alternatives = $result->getAlternatives();
            $mostLikely = $alternatives[0];
            $transcription .= $mostLikely->getTranscript();
        }
        return $transcription;
    }
}
