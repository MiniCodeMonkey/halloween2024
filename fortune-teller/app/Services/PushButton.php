<?php

namespace App\Services;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;
use Throwable;

class PushButton
{
    private ?Pin $buttonPin;

    public function __construct(int $pinNumber)
    {
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
        return $this->buttonPin === null || $this->buttonPin->isOn();
    }
}
