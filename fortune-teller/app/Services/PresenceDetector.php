<?php

namespace App\Services;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;

class PresenceDetector
{
    private Pin $pirSensor;

    public function __construct()
    {
        $this->pirSensor = Pinout::pin(config('pinouts.push_button'));
        $this->pirSensor->makeInput();
    }

    public function isPresent(): bool
    {
        return $this->pirSensor->isOn();
    }
}
