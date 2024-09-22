<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;

class DMXLightsManager
{
    private $universe;
    private $channels;
    private $startAddress;
    private Client $client;

    public function __construct($universe = 1, $startAddress = 1)
    {
        $this->universe = $universe;
        $this->startAddress = $startAddress;
        $this->channels = array_fill(0, 512, 0);
        $this->client = new Client();
    }

    public function setBrightness(int $value): self
    {
        $this->setChannel(1, $value);

        return $this;
    }

    public function setColor(int $red, int $green, int $blue): self
    {
        $this->setChannel(2, $red);
        $this->setChannel(3, $green);
        $this->setChannel(4, $blue);

        return $this;
    }

    public function setStrobe($value): self
    {
        $this->setChannel(5, $value);

        return $this;
    }

    private function setChannel(int $channel, int $value): void
    {
        $value = max(0, min(255, $value));
        $this->channels[$this->startAddress + $channel - 2] = $value;
    }

    public function apply(): void
    {
        $dmx_data = implode(',', $this->channels);

        $response = $this->client->post('http://localhost:9090/set_dmx', [
            'form_params' => [
                'u' => $this->universe,
                'd' => $dmx_data
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to send DMX data via OLA HTTP API");
        }
    }
}
