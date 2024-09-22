<?php

namespace App\Services;

use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
use RuntimeException;

class SpeechToTextProcessor
{
    private SpeechClient $speechClient;

    public function __construct(SpeechClient $speechClient)
    {
        $this->speechClient = $speechClient;
    }

    public function transcribe($filename)
    {
        $filename = storage_path($filename);
        $content = file_get_contents($filename);
        $audio = (new RecognitionAudio())->setContent($content);

        $config = (new RecognitionConfig())
            ->setEncoding(AudioEncoding::LINEAR16)
            ->setSampleRateHertz(16000)
            ->setLanguageCode('da-DK')
            ->setAudioChannelCount(1);

        $operation = $this->speechClient->longRunningRecognize($config, $audio);
        $operation->pollUntilComplete();

        if ($operation->operationSucceeded()) {
            $response = $operation->getResult();
            $transcription = '';
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                $transcription .= $mostLikely->getTranscript();
            }
            return $transcription;
        } else {
            throw new RuntimeException($operation->getError());
        }
    }
}
