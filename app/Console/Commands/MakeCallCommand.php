<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class MakeCallCommand extends Command
{
    protected $signature = 'freeswitch:call
                            {gateway : The gateway name}
                            {to : The destination number}';

    protected $description = 'Originate a call via FreeSWITCH using specified gateway and destination number.';

    protected string $freeswitchBin;

    public function __construct()
    {
        parent::__construct();
        $this->freeswitchBin = base_path('freeswitch/_install/bin');
    }

    public function handle(): int
    {
        $gateway = $this->argument('gateway');
        $to = $this->argument('to');

        $this->info("Originating call to {$to} via gateway {$gateway}...");

        // Construct originate command with &echo(
        $cmd = "originate sofia/gateway/{$gateway}/{$to} &echo()";

        $fullCmd = "{$this->freeswitchBin}/fs_cli -x \"$cmd\"";

        $this->info("Running command: $fullCmd");

        $process = Process::run($fullCmd);

        if ($process->successful()) {
            $this->info("Call originated successfully.");
            $this->line($process->output());
            return self::SUCCESS;
        } else {
            $this->error("Failed to originate call.");
            $this->line($process->output());
            $this->line($process->errorOutput());
            return self::FAILURE;
        }
    }
}
