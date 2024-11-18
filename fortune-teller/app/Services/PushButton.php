<?php

namespace App\Services;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;
use Illuminate\Console\OutputStyle;
use Throwable;

class PushButton
{
    private ?Pin $buttonPin;
    private ?OutputStyle $consoleOutput;

    public function __construct(int $pinNumber, ?OutputStyle $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;

        try {
            $this->buttonPin = Pinout::pin($pinNumber);
            $this->buttonPin->makeInput();
        } catch (Throwable $e) {
            $this->buttonPin = null;
            info('Push button not available');
        }
    }

    public function isPushed(): bool
    {
        if ($this->buttonPin === null && $this->consoleOutput) {
            return $this->consoleOutput->confirm('Ready to continue?');
        }

        return $this->buttonPin->isOn();
    }
}
