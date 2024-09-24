<?php

namespace App\Console\Commands;

use App\Services\AudioGenerator;
use App\Services\AudioRecorder;
use App\Services\DMXLightsManager;
use App\Services\PredictionMaker;
use App\Services\PresenceDetector;
use App\Services\Relay;
use App\Services\SpeechToTextProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
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
    private PresenceDetector $presenceDetector;

    /**
     * Execute the console command.
     */
    public function handle(SpeechToTextProcessor $speechToTextProcessor, AudioGenerator $audioGenerator, PredictionMaker $predictionMaker, AudioRecorder $audioRecorder, PresenceDetector $presenceDetector)
    {
        App::setLocale('da');

        $this->speechToTextProcessor = $speechToTextProcessor;
        $this->audioGenerator = $audioGenerator;
        $this->predictionMaker = $predictionMaker;
        $this->audioRecorder = $audioRecorder;
        $this->presenceDetector = $presenceDetector;

        $this->frontLights = new Relay(config('pinouts.front_lights'));
        $this->frontLights->turnOn();

        $this->magicBall = new Relay(config('pinouts.magic_ball'));
        $this->magicBall->turnOff();

        $this->parLight = new DMXLightsManager(1, 1);
        $this->parLight->setBrightness(255)->setColor(255, 0, 0)->setStrobe(0)->apply();

        while (true) {
            if ($this->presenceDetector->isPresent()) {
                try {
                    $this->handleSession();
                } catch (Throwable $e) {
                    $this->error($e->getMessage());
                    $this->audioGenerator->say(__('fortune-teller.error-occurred'));
                } finally {
                    $this->closeSession();
                }

                sleep(3);

                $this->frontLights->turnOn();
            }
            sleep(1);
        }
    }

    private function handleSession(bool $withIntroduction = true): void
    {
        $this->parLight->setBrightness(255)->setColor(0, 0, 255)->apply();
        $this->magicBall->turnOff();

        if ($withIntroduction) {
            $this->audioGenerator->say(__('fortune-teller.introduction'));
        }

        $this->magicBall->turnOn();

        $this->line('Listening...');
        if ($filename = $this->audioRecorder->record(10)) {
            $this->parLight->setBrightness(255)->setColor(255, 0, 255)->setStrobe(100)->apply();
            $this->audioGenerator->say(__('fortune-teller.processing1'));

            $userInput = $this->speechToTextProcessor->transcribe($filename);
            $this->line("Heard: $userInput");

            $this->audioGenerator->say(__('fortune-teller.processing2'));

            $this->parLight->setBrightness(255)->setColor(0, 0, 255)->setStrobe(0)->apply();

            if (empty($userInput)) {
                $this->audioGenerator->say(__('fortune-teller.nothing-transcribed'));
                $this->handleSession(false);
            } else {
                $response = $this->predictionMaker->makePrediction($userInput);
                $this->line("AI says: $response");

                $this->audioGenerator->say(__('fortune-teller.processing3'));

                $this->magicBall->turnOff();

                $this->audioGenerator->say($response);
            }
        }
    }

    private function closeSession()
    {
        $this->frontLights->turnOff();
        $this->magicBall->turnOff();
        $this->parLight->setBrightness(0)->apply();
    }

}
