<?php

namespace App\Services;

use Anthropic\Laravel\Facades\Anthropic;
use Exception;

class PredictionMaker
{
    public function makePrediction(string $userInput, int $attempts = 0): ?string
    {
        try {
            $result = Anthropic::messages()->create([
                'model' => config('anthropic.model'),
                'max_tokens' => 300,
                'system' => __('fortune-teller.llm-instructions'),
                'messages' => [
                    ['role' => 'user', 'content' => $userInput]
                ]
            ]);

            return $this->transformAIResponse($result->content[0]->text);
        } catch (Exception $e) {
            info('Claude: ' . $e->getMessage());
            $attempts++;

            if ($attempts <= 3) {
                sleep($attempts * 5);
                info('Retrying...');

                return $this->makePrediction($userInput, ++$attempts);
            }
        }

        return null;
    }

    private function transformAIResponse(?string $text): string
    {
        if (empty($text)) {
            return __('fortune-teller.llm-error');
        }

        // Remove any text between asterisks (doesn't work well with the text-to-speech service)
        $pattern = '/\*(.*?)\*/m';
        $text = preg_replace($pattern, '', $text);

        // Remove any "hmm" sounds
        $pattern = '/(\b|,)(hmm|aha)(\b|,)/im';
        return preg_replace($pattern, '', $text);
    }
}
