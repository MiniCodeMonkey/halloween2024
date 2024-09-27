<?php

namespace App\Services;

use DanJohnson95\Pinout\Entities\Pin;
use DanJohnson95\Pinout\Pinout;
use Throwable;

class Relay
{
    private ?Pin $pin;

    public function __construct(int $pinNumber)
    {
        try {
            $this->pin = Pinout::pin($pinNumber);
            $this->pin->makeOutput();
        } catch (Throwable $e) {
            $this->pin = null;
            info('Pin ' . $pinNumber . ' could not be instantiated');
        }
    }

    public function turnOn(): void
    {
        if ($this->pin) {
            $this->pin->turnOff(); // Yes, this is opposite of what you'd expect
        }
    }

    public function turnOff(): void
    {
        if ($this->pin) {
            $this->pin->turnOn(); // Yes, this is opposite of what you'd expect
        }
    }

    public function isOn(): ?bool
    {
        if ($this->pin) {
            return $this->pin->isOff(); // Yes, this is opposite of what you'd expect
        }

        return null;
    }

    public function toggle(): void
    {
        if ($isOn = $this->isOn() !== null) {
            if ($isOn) {
                $this->turnOff();
            } else {
                $this->turnOn();
            }
        }
    }
}
