<?php

namespace App\Console\Commands;

use App\Services\AudioGenerator;
use App\Services\AudioRecorder;
use App\Services\DMXLightsManager;
use App\Services\PredictionMaker;
use App\Services\PushButton;
use App\Services\Relay;
use App\Services\SpeechToTextProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Throwable;

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

    private SpeechToTextProcessor $speechToTextProcessor;
    private AudioGenerator $audioGenerator;
    private PredictionMaker $predictionMaker;
    private AudioRecorder $audioRecorder;
    private Relay $frontLights;
    private Relay $magicBall;
    private DMXLightsManager $parLight;
    private PushButton $pushButton;
    private Carbon $lastTauntTime;
    private ?string $lastTauntKey = null;

    /**
     * Execute the console command.
     */
    public function handle(SpeechToTextProcessor $speechToTextProcessor, AudioGenerator $audioGenerator, PredictionMaker $predictionMaker, AudioRecorder $audioRecorder)
    {
        $this->speechToTextProcessor = $speechToTextProcessor;
        $this->audioGenerator = $audioGenerator;
        $this->predictionMaker = $predictionMaker;
        $this->audioRecorder = $audioRecorder;
        $this->pushButton = new PushButton(config('pinouts.push_button'));

        $this->frontLights = new Relay(config('pinouts.front_lights'));
        $this->frontLights->turnOn();

        $this->magicBall = new Relay(config('pinouts.magic_ball'));
        $this->magicBall->turnOff();

        $this->parLight = new DMXLightsManager(1, 1);
        $this->parLight
            ->setBrightness(255)
            ->setColor(255, 0, 0)
            ->setStrobe(0)
            ->apply();

        $this->line('We\'re live with locale: ' . App::getLocale());

        $this->preloadRecordings();
        $this->taunt();

        while (true) {
            if ($this->pushButton->isPushed()) {
                try {
                    $this->handleSession();
                } catch (Throwable $e) {
                    $this->error($e->getMessage());
                    $this->audioGenerator->say(__('fortune-teller.error-occurred'));
                } finally {
                    $this->closeSession();
                }

                sleep(5);

                $this->frontLights->turnOn();
            } else {
                usleep(1_000_000 / 25);
                $this->tauntIfNeeded();
            }
        }
    }

    private function handleSession(bool $withIntroduction = true, int $attempts = 0): void
    {
        $this->parLight
            ->setBrightness(255)
            ->setColor(0, 255, 255)
            ->setStrobe(0)
            ->apply();

        $this->magicBall->turnOff();

        if ($withIntroduction) {
            $this->audioGenerator->say(__('fortune-teller.introduction'));
        }

        $this->magicBall->turnOn();

        $this->line('Listening...');
        if ($filename = $this->audioRecorder->record(7)) {
            $this->parLight->setStrobe(200)->apply();
            $this->audioGenerator->sayAsync(__('fortune-teller.processing_intro'));

            $this->speechToTextProcessor->transcribe($filename);
            $userInput = $this->speechToTextProcessor->getTranscription();
            $this->line("Heard: $userInput");

            $this->parLight->setStrobe(0)->apply();

            if (empty($userInput)) {
                if ($attempts < 2) {
                    $this->audioGenerator->say(__('fortune-teller.nothing-transcribed'));
                    $this->handleSession(false, $attempts + 1);
                }
            } else {
                $this->line('Waiting for sound to finish...');
                $this->audioGenerator->blockWhilePlaying();
                $this->audioGenerator->sayAsync(__('fortune-teller.processing_' . mt_rand(0, 9)));

                $response = $this->predictionMaker->makePrediction($userInput);
                $this->line('Got LLM response...');
                $this->line('Caching audio while sound is playing...');
                $this->audioGenerator->cache($response);

                $this->line('Waiting for sound to finish...');
                $this->audioGenerator->blockWhilePlaying();
                $this->audioGenerator->say($response);
            }
        }
    }

    private function closeSession(): void
    {
        sleep(1);
        $this->frontLights->turnOff();
        $this->magicBall->turnOff();
        $this->parLight->setBrightness(0)->apply();
    }

    private function preloadRecordings(): void
    {
        $this->line('Preloading recordings...');
        $translations = Lang::getLoader()->load(App::getLocale(), 'fortune-teller');
        foreach ($translations as $key => $translation) {
            if ($key !== 'llm-instructions') {
                $this->audioGenerator->cache($translation);
            }
        }
    }

    private function taunt(): void
    {
        $this->line('Taunting...');
        $tauntsCount = config('fortune-teller.taunts.count');

        do {
            $translationKey = 'fortune-teller.taunt_' . mt_rand(1, $tauntsCount);
        } while ($translationKey === $this->lastTauntKey);

        if (trans()->has($translationKey)) {
            $this->lastTauntTime = now();
            $this->lastTauntKey = $translationKey;
            $this->audioGenerator->sayAsync(__($translationKey));
        }
    }

    private function tauntIfNeeded(): void
    {
        if ($this->lastTauntTime->diffInSeconds(now()) > mt_rand(...config('fortune-teller.taunts.seconds_between'))) {
            $this->taunt();
        }
    }

}
