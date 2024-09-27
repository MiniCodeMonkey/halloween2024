<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use InvalidArgumentException;

class Led
{
    private string $pwmChip = '/sys/class/pwm/pwmchip0';
    private string $pwm = '/sys/class/pwm/pwmchip0/pwm0';
    private int $period = 1000000; // 1kHz frequency

    public function __construct()
    {
        $this->exportPWM();
        $this->setPeriod();
    }

    private function exportPWM(): void
    {
        if (!file_exists($this->pwm)) {
            Process::run("echo 0 > {$this->pwmChip}/export");
        }
    }

    private function setPeriod(): void
    {
        Process::run("echo {$this->period} > {$this->pwm}/period");
    }

    public function setBrightness(float $percentage): void
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException("Brightness must be between 0 and 100");
        }

        $dutyCycle = intval($this->period * ($percentage / 100));
        Process::run("echo {$dutyCycle} > {$this->pwm}/duty_cycle");
    }

    public function enable(): void
    {
        Process::run("echo 1 > {$this->pwm}/enable");
    }

    public function disable(): void
    {
        Process::run("echo 0 > {$this->pwm}/enable");
    }
}
