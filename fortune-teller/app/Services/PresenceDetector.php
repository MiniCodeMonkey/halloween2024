<?php

namespace App\Services;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;
use Throwable;

class PresenceDetector
{
    private ?Pin $pirSensor;

    public function __construct()
    {
        try {
            $this->pirSensor = Pinout::pin(config('pinouts.push_button'));
            $this->pirSensor->makeInput();
        } catch (Throwable $e) {
            $this->pirSensor = null;
            info('Presence detector not available');
        }
    }

    public function isPresent(): bool
    {
        return $this->pirSensor === null || $this->pirSensor->isOn();
    }
}
