<?php

namespace App\Console\Commands;

use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\SignalableCommandInterface;

define('JOBS', [
    'update_taxonomy_where'
]);

class HoquServer extends Command implements SignalableCommandInterface {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geomixer:hoqu_server';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an instance of Geomixer HOQU server';
    private bool $interrupted;
    private HoquServiceProvider $hoquServiceProvider;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(HoquServiceProvider $hoquServiceProvider) {
        parent::__construct();

        $this->hoquServiceProvider = $hoquServiceProvider;
        $this->interrupted = false;
    }

    /**
     * Get the list of signals handled by the command.
     *
     * @return array
     */
    public function getSubscribedSignals(): array {
        return [SIGINT];
    }

    /**
     * Handle the command line signals
     *
     * @param int $signal the signal number
     */
    public function handleSignal(int $signal): void {
        switch ($signal) {
            case SIGINT:
                Log::channel('stdout')->warning("  [CTRL - C] Performing soft interruption. Terminating job before closing");
                $this->interrupted = true;
                break;
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        while (!$this->interrupted) {
            $result = $this->executeHoquServer();
            if (!$result)
                sleep(5);
        }

        return 0;
    }

    /**
     * Retrieve a hoqu jobs and execute it
     *
     * @return bool true if a job has been executed
     */
    public function executeHoquServer(): bool {
        try {
            Log::channel('stdout')->info('Retrieving new job from HOQU');
            $job = $this->hoquServiceProvider->pull(JOBS);
            $error = false;
            $errorLog = '';
            $log = '';
            if (isset($job['id'])) {
                try {
                    $parameters = json_decode($job['parameters'], true);

                    switch ($job["job"]) {
                        case 'update_taxonomy_where';
                            $controller = App::make('\App\Http\Controllers\TaxonomyWhere');
                            App::call([$controller, 'updateJob'], [$parameters]);
                            break;
                        default:
                            $error = true;
                            $errorLog = 'The job ' . $job['job'] . ' is not currently supported';
                            break;
                    }
                } catch (Exception $e) {
                    $error = true;
                    $errorLog = $e->getMessage();
                }

                if ($error) {
                    Log::channel('stdout')->error('The job did not complete: ' . $errorLog);
                    $this->hoquServiceProvider->updateError($job['id'], $errorLog, $log);
                } else {
                    Log::channel('stdout')->info('Job completed successfully');
                    $this->hoquServiceProvider->updateDone($job['id'], $log);
                }
            } else {
                Log::channel('stdout')->info('No jobs available');

                return false;
            }
        } catch (Exception $e) {
            Log::channel('stdout')->info($e->getMessage());

            return false;
        }

        return true;
    }
}