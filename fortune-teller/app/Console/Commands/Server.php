<?php

namespace App\Console\Commands;

use App\Services\AudioGenerator;
use App\Services\AudioRecorder;
use App\Services\DMXLightsManager;
use App\Services\PredictionMaker;
use App\Services\PushButton;
use App\Services\Relay;
use App\Services\SpeechToTextProcessor;
use Illuminate\Console\Command;
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

        $this->line('We\'re live with locale: ' . app()->getLocale());
        $this->audioGenerator->say(__('fortune-teller.awake'));

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
        if ($filename = $this->audioRecorder->record(10)) {
            $this->parLight->setStrobe(100)->apply();
            $this->audioGenerator->say(__('fortune-teller.processing1'));

            $this->speechToTextProcessor->transcribe($filename);
            $this->audioGenerator->say(__('fortune-teller.processing2')); // This is essentially our "loading" message
            $userInput = $this->speechToTextProcessor->getTranscription();
            $this->line("Heard: $userInput");

            $this->parLight->setStrobe(0)->apply();

            if (empty($userInput)) {
                if ($attempts < 2) {
                    $this->audioGenerator->say(__('fortune-teller.nothing-transcribed'));
                    $this->handleSession(false, $attempts + 1);
                }
            } else {
                $response = $this->predictionMaker->makePrediction($userInput);
                $this->line("AI says: $response");

                // $this->audioGenerator->say(__('fortune-teller.processing3'));

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

}
