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
                    $this->audioGenerator->say('Jeg kan desværre ikke spå din fremtid lige nu. Prøv igen senere.');
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
            $this->audioGenerator->say('Velkommen til Den Mystiske Spåkones Bod! Spørg om din fremtid eller søg visdom fra det hinsides. Så sig mig. Hvad kan jeg spå for dig?');
        }

        $this->magicBall->turnOn();

        $this->line('Listening...');
        if ($filename = $this->audioRecorder->record(10)) {
            $this->parLight->setBrightness(255)->setColor(255, 0, 255)->setStrobe(100)->apply();
            $this->audioGenerator->say('Jeg kigger i min krystalkugle...');

            $userInput = $this->speechToTextProcessor->transcribe($filename);
            $this->line("Heard: $userInput");

            $this->audioGenerator->say('Aaah ja... Lad mig se...');

            $this->parLight->setBrightness(255)->setColor(0, 0, 255)->setStrobe(0)->apply();

            if (empty($userInput)) {
                $this->audioGenerator->say('Jeg hørte ikke noget. Kan du gentage det?');
                $this->handleSession(false);
            } else {
                $response = $this->predictionMaker->makePrediction($userInput);
                $this->line("AI says: $response");

                $this->audioGenerator->say('Krystalkuglens svar er sikkert og vist. Dens visdom er urokkelig og den tager aldrig fejl... Lad os se...');

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
