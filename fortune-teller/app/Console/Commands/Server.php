<?php

namespace App\Console\Commands;

use Anthropic\Laravel\Facades\Anthropic;
use Aws\Exception\AwsException;
use Aws\Polly\PollyClient;
use Exception;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
use Illuminate\Console\Command;
use RuntimeException;

class Server extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the fortune teller server';

    private SpeechClient $speechClient;
    private PollyClient $pollyClient;

    const MODEL_INSTRUCTIONS = "Du er en venlig og underholdende 'spåkone' ved en familievenlig halloweenfest. Lyt og svar altid på dansk. Din rolle er at give positive, opmuntrende og sjove svar på gæsternes spørgsmål. Brug lette halloweenreferencer og efterårstemaer i dine svar, men hold det hyggeligt og ikke skræmmende. Dine 'spådomme' skal være kreative, positive og passende for alle aldre. Giv gerne gode råd eller opmuntringer, når det er relevant. Husk at holde svarene korte og muntre. Tilpas dine svar til dansk kultur og traditioner, når det er passende. Dit mål er at få gæsterne til at smile og have det sjovt ved festen. Undgå at bruge fyldeord som \"Hmm\". Brug ikke ord der ikke er danske.";

    /**
     * Execute the console command.
     */
    public function handle(SpeechClient $speechClient, PollyClient $pollyClient)
    {
        $this->speechClient = $speechClient;
        $this->pollyClient = $pollyClient;

        $this->say('Velkommen til Den Mystiske Spåkones Bod! Spørg om din fremtid eller søg visdom fra det hinsides. Så sig mig. Hvad kan jeg spå for dig?');

        while (true) {
            $this->line('Listening...');
            if ($this->recordAndResample()) {
                $user_input = $this->transcribe('input_16k.wav');
                $this->line("User says: $user_input");

                if (!empty($user_input)) {
                    $response = $this->makePrediction($user_input);
                    $this->line("AI says: $response");

                    $this->say($response);

                    sleep(2);
                    $this->say('Er der andet som jeg kan spå for dig?');
                    sleep(2);
                }
            }
        }
    }

    function transcribe($filename)
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

    function record($filename = 'input_48k.wav', $max_duration = 30)
    {
        $filename = storage_path($filename);
        $cmd = "rec -q -t wav -r 48000 -c 1 -b 16 $filename gain 10 silence 1 0.1 3% 1 1.0 3% trim 0 $max_duration";
        exec($cmd, $output, $return_var);

        return $return_var === 0;
    }

    function resample($input_file = 'input_48k.wav', $output_file = 'input_16k.wav')
    {
        $input_file = storage_path($input_file);
        $output_file = storage_path($output_file);
        $cmd = "sox $input_file -r 16000 $output_file";

        exec($cmd, $output, $return_var);

        return $return_var === 0;
    }

    private function recordAndResample(): bool
    {
        return $this->record() && $this->resample();
    }

    function play($filename)
    {
        exec("afplay $filename");
    }

    private function say(string $message): bool
    {
        $message = trim($message);
        $messageKey = md5($message);

        $filename = storage_path("app/messages/$messageKey.mp3");
        if (!file_exists($filename)) {
            try {
                $result = $this->pollyClient->synthesizeSpeech([
                    'OutputFormat' => 'mp3',
                    'Text' => $message,
                    'TextType' => 'text',
                    'VoiceId' => 'Sofie',
                    'Engine' => 'neural',
                ]);

                file_put_contents($filename, $result['AudioStream']->getContents());
            } catch (AwsException $e) {
                error_log("Error in text-to-speech: " . $e->getMessage());
                return false;
            }
        }

        $this->play($filename);
        return true;
    }

    private function makePrediction(string $userInput, int $attempts = 0): ?string
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
            $this->error('Claude: ' . $e->getMessage());
            $attempts++;

            if ($attempts <= 3) {
                sleep($attempts * 5);
                $this->line('Retrying...');

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
        $pattern = '/(\b|,)hmm(\b|,)/im';
        return preg_replace($pattern, '', $text);
    }

}
