<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Class FreeswitchManagerCommand
 *
 * Artisan command to manage the FreeSWITCH PBX server.
 * Supports starting, stopping, monitoring, restarting,
 * reloading configurations, tailing logs, running sofia commands, and more.
 *
 * Usage examples:
 *   php artisan freeswitch start
 *   php artisan freeswitch stop
 *   php artisan freeswitch restart
 *   php artisan freeswitch status
 *   php artisan freeswitch logs
 *   php artisan freeswitch console
 *   php artisan freeswitch sofia status profile external
 */
class FreeswitchManagerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freeswitch {action : The action to perform (start, stop, monitor, restart, status, logs, console, reload-xml, reload-sip, sofia, sofia-status, is-running)} {args?* : Additional arguments for sofia command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manages the FreeSWITCH PBX (start, stop, monitor, sofia commands, and more).';

    /**
     * Path to FreeSWITCH binary directory.
     *
     * @var string
     */
    protected string $freeswitchBin;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Adjust this path if your FreeSWITCH binary is located elsewhere
        $this->freeswitchBin = base_path('freeswitch/_install/bin');
    }

    /**
     * Execute the console command.
     *
     * Routes the requested action to the corresponding handler.
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'start'        => $this->start(),
            'stop'         => $this->stop(),
            'monitor'      => $this->monitor(),
            'status'       => $this->status(),
            'restart'      => $this->restart(),
            'reload-xml'   => $this->reloadXml(),
            'reload-sip'   => $this->reloadSofia(),
            'is-running'   => $this->isRunning() ? self::SUCCESS : self::FAILURE,
            'logs'         => $this->logs(),
            'console'      => $this->console(),
            'sofia-status' => $this->sofiaStatus(),
            'sofia'        => $this->handleSofia($this->argument('args') ?? []),
            default        => $this->handleHelp(),
        };
    }

    /**
     * Displays help information about the available actions.
     *
     * @return int
     */
    protected function handleHelp(): int
    {
        $this->info('Unknown or missing action. Available actions:');
        $this->line('start           Start FreeSWITCH server');
        $this->line('stop            Stop FreeSWITCH server gracefully');
        $this->line('restart         Restart FreeSWITCH server');
        $this->line('status          Check if FreeSWITCH is running');
        $this->line('is-running      Returns success if running, failure otherwise');
        $this->line('logs            Tail the FreeSWITCH log file');
        $this->line('console         Attach to the FreeSWITCH CLI console');
        $this->line('monitor         Alias for console');
        $this->line('reload-xml      Reload XML configuration');
        $this->line('reload-sip      Reload Sofia SIP profiles');
        $this->line('sofia-status    Show status of Sofia profiles');
        $this->line('sofia           Run arbitrary sofia commands');
        $this->line('');
        $this->line('Example sofia command:');
        $this->line('  php artisan freeswitch sofia status profile external');
        return self::FAILURE;
    }

    /**
     * Start the FreeSWITCH server.
     *
     * @return int
     */
    protected function start(): int
    {
        $this->info('Starting FreeSWITCH...');

        $process = Process::run("{$this->freeswitchBin}/freeswitch -nc");

        // FreeSWITCH outputs to stderr even on success, check for 'Backgrounding' string
        if ($process->successful() || str_contains($process->errorOutput(), 'Backgrounding')) {
            $this->info('FreeSWITCH started successfully.');
            return self::SUCCESS;
        }

        $this->error('Failed to start FreeSWITCH.');
        $this->line($process->output());
        $this->line($process->errorOutput());
        return self::FAILURE;
    }

    /**
     * Stop the FreeSWITCH server gracefully.
     * If fails, force kills the process.
     *
     * @return int
     */
    protected function stop(): int
    {
        $this->info('Stopping FreeSWITCH...');

        $stop = Process::run("{$this->freeswitchBin}/freeswitch -stop");

        if ($stop->successful()) {
            $this->info('FreeSWITCH stopped gracefully.');
            return self::SUCCESS;
        } else {
            $this->warn("Graceful stop failed. Output:");
            $this->line($stop->output());
            $this->line($stop->errorOutput());
        }

        $kill = Process::run('pkill -f freeswitch');

        if ($kill->successful()) {
            $this->info('FreeSWITCH was forcefully stopped.');
            return self::SUCCESS;
        } else {
            $this->error("Force kill failed. Output:");
            $this->line($kill->output());
            $this->line($kill->errorOutput());
        }

        $this->error('Failed to stop FreeSWITCH.');
        return self::FAILURE;
    }

    /**
     * Attach to FreeSWITCH console (fs_cli).
     *
     * @return int
     */
    protected function monitor(): int
    {
        $this->info('Attaching to FreeSWITCH console...');
        passthru($this->freeswitchBin . '/fs_cli');
        return self::SUCCESS;
    }

    /**
     * Alias to monitor()
     *
     * @return int
     */
    protected function console(): int
    {
        return $this->monitor();
    }

    /**
     * Check if FreeSWITCH is currently running.
     *
     * @return int
     */
    protected function status(): int
    {
        $this->info('Checking FreeSWITCH status...');

        $check = Process::run('pgrep -f freeswitch');

        if ($check->successful() && trim($check->output()) !== '') {
            $this->info('FreeSWITCH is running. PID(s):');
            $this->line($check->output());
            return self::SUCCESS;
        }

        $this->warn('FreeSWITCH is not running.');
        return self::FAILURE;
    }

    /**
     * Reload FreeSWITCH XML configuration.
     *
     * @return int
     */
    protected function reloadXml(): int
    {
        $this->info('Reloading XML configuration...');
        passthru($this->freeswitchBin . '/fs_cli -x "reloadxml"');
        return self::SUCCESS;
    }

    /**
     * Reload Sofia SIP profiles.
     *
     * @return int
     */
    protected function reloadSofia(): int
    {
        $this->info('Reloading Sofia SIP profiles...');
        passthru($this->freeswitchBin . '/fs_cli -x "reload mod_sofia"');
        return self::SUCCESS;
    }

    /**
     * Tail the FreeSWITCH log file.
     *
     * @return int
     */
    protected function logs(): int
    {
        $logfile = base_path("freeswitch/_install/var/log/freeswitch/freeswitch.log");

        if (!file_exists($logfile)) {
            $this->error("Log file not found at {$logfile}");
            return self::FAILURE;
        }

        $this->info("Tailing FreeSWITCH logs at {$logfile}...");
        passthru("tail -f {$logfile}");
        return self::SUCCESS;
    }

    /**
     * Check if FreeSWITCH process is running (returns bool).
     *
     * @return bool
     */
    protected function isRunning(): bool
    {
        $process = Process::run("pgrep -f '{$this->freeswitchBin}/freeswitch'");
        return $process->successful() && !empty(trim($process->output()));
    }

    /**
     * Restart FreeSWITCH server.
     *
     * @return int
     */
    protected function restart(): int
    {
        $this->info('Restarting FreeSWITCH...');

        if ($this->isRunning()) {
            $this->info('FreeSWITCH is currently running, stopping it first...');
            passthru("{$this->freeswitchBin}/freeswitch -stop");
            sleep(2); // brief delay to allow shutdown
        } else {
            $this->info('FreeSWITCH is not running.');
        }

        $this->info('Starting FreeSWITCH...');
        passthru("{$this->freeswitchBin}/freeswitch -nc");

        $this->info('Restart command completed.');

        return self::SUCCESS;
    }

    /**
     * Show status of Sofia SIP profiles.
     *
     * @return int
     */
    protected function sofiaStatus(): int
    {
        $this->info('Fetching Sofia profiles status...');
        $process = Process::run("{$this->freeswitchBin}/fs_cli -x 'sofia status'");

        if ($process->successful()) {
            $this->line($process->output());
            return self::SUCCESS;
        }

        $this->error('Failed to get Sofia status.');
        $this->line($process->errorOutput());
        return self::FAILURE;
    }

    /**
     * Handle arbitrary sofia commands.
     *
     * Usage: php artisan freeswitch sofia <sofia subcommands ...>
     *
     * @param array $sofiaCommand Array of sofia command parts
     * @return int
     */
    protected function handleSofia(array $sofiaCommand): int
    {
        if (empty($sofiaCommand)) {
            $this->error('No sofia command specified.');
            return self::FAILURE;
        }

        $cmd = "{$this->freeswitchBin}/fs_cli -x '" . implode(' ', $sofiaCommand) . "'";

        $this->info("Running sofia command: $cmd");
        passthru($cmd);
        return self::SUCCESS;
    }
}
