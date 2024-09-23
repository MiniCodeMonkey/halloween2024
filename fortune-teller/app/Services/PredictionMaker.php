<?php

namespace App\Services;

use Anthropic\Laravel\Facades\Anthropic;
use Exception;

class PredictionMaker
{
    const MODEL_INSTRUCTIONS = "Du er en venlig og underholdende 'spåkone' ved en familievenlig halloweenfest. Lyt og svar altid på dansk. Din rolle er at give positive, opmuntrende og sjove svar på gæsternes spørgsmål. Brug lette halloweenreferencer og efterårstemaer i dine svar, men hold det hyggeligt og ikke skræmmende. Dine 'spådomme' skal være kreative, positive og passende for alle aldre. Giv gerne gode råd eller opmuntringer, når det er relevant. Husk at holde svarene korte og muntre. Tilpas dine svar til dansk kultur og traditioner, når det er passende. Dit mål er at få gæsterne til at smile og have det sjovt ved festen. Undgå at bruge fyldeord som \"Hmm\". Brug ikke ord der ikke er danske.";

    public function makePrediction(string $userInput, int $attempts = 0): ?string
    {
        try {
            $result = Anthropic::messages()->create([
                'model' => config('anthropic.model'),
                'max_tokens' => 300,
                'system' => self::MODEL_INSTRUCTIONS,
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
            return 'Jeg er desværre ikke i stand til at spå din fremtid lige nu. Prøv igen senere.';
        }

        // Remove any text between asterisks (doesn't work well with the text-to-speech service)
        $pattern = '/\*(.*?)\*/m';
        $text = preg_replace($pattern, '', $text);

        // Remove any "hmm" sounds
        $pattern = '/(\b|,)(hmm|aha)(\b|,)/im';
        return preg_replace($pattern, '', $text);
    }
}
