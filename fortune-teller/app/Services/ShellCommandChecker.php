<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ShellCommandChecker
{
    public static function doesCommandExist(string $cmd): bool
    {
        $process = new Process(['command', '-v', $cmd]);
        $process->run();

        return $process->isSuccessful();
    }
}
