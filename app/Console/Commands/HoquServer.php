<?php

namespace App\Console\Commands;

use App\Providers\HoquJobs\EcTrackJobsServiceProvider;
use App\Providers\HoquJobs\EcMediaJobsServiceProvider;
use App\Providers\HoquJobs\EcPoiJobsServiceProvider;
use App\Providers\HoquJobs\MBTilesJobsServiceProvider;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\SignalableCommandInterface;

define('ENRICH_EC_MEDIA', 'enrich_ec_media');
define('DELETE_EC_MEDIA_IMAGES', 'delete_ec_media_images');
define('ENRICH_EC_TRACK', 'enrich_ec_track');
define('ENRICH_EC_POI', 'enrich_ec_poi');
define('UPDATE_GEOMIXER_TAXONOMY_WHERE', 'update_geomixer_taxonomy_where');
define('UPDATE_UGC_TAXONOMY_WHERES', 'update_ugc_taxonomy_wheres');
define('GENERATE_MBTILES_SQUARE', 'generate_mbtiles_square');

define('JOBS', [
    ENRICH_EC_MEDIA,
    DELETE_EC_MEDIA_IMAGES,
    ENRICH_EC_TRACK,
    ENRICH_EC_POI,
    UPDATE_GEOMIXER_TAXONOMY_WHERE,
    UPDATE_UGC_TAXONOMY_WHERES,
    GENERATE_MBTILES_SQUARE
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
    public array $jobs = JOBS;
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
     * Calculate the server executable jobs
     */
    public function initializeJobs() {
        if (config('geomixer.hoqu.jobs_supported') != null || config('geomixer.hoqu.jobs_not_supported') != null) {
            if (config('geomixer.hoqu.jobs_supported') != null) {
                $supported = explode(',', str_replace(' ', '', config('geomixer.hoqu.jobs_supported')));
                $jobs = [];
                foreach ($supported as $key => $job) {
                    if (in_array($job, JOBS))
                        $jobs[] = $job;
                }
            } else $jobs = JOBS;

            if (config('geomixer.hoqu.jobs_not_supported') != null) {
                $notSupported = explode(',', str_replace(' ', '', config('geomixer.hoqu.jobs_not_supported')));
                foreach ($notSupported as $key => $job) {
                    if (in_array($job, $jobs)) {
                        unset($jobs[array_search($job, $jobs)]);
                    }
                }
            }

            $this->jobs = $jobs;
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        $this->initializeJobs();
        while (!$this->interrupted) {
            $result = $this->executeHoquJob();
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
    public function executeHoquJob(): bool {
        try {
            Log::channel('stdout')->info('Retrieving new job from HOQU');
            $job = $this->hoquServiceProvider->pull($this->jobs);
            $error = false;
            $errorLog = '';
            $log = '';
            if (isset($job['id'])) {
                try {
                    $parameters = json_decode($job['parameters'], true);

                    switch ($job["job"]) {
                        case ENRICH_EC_TRACK;
                            $service = app(EcTrackJobsServiceProvider::class);
                            $service->enrichJob($parameters);
                            break;
                        case ENRICH_EC_MEDIA;
                            $service = app(EcMediaJobsServiceProvider::class);
                            $service->enrichJob($parameters);
                            break;
                        case DELETE_EC_MEDIA_IMAGES;
                            $service = app(EcMediaJobsServiceProvider::class);
                            $service->deleteImagesJob($parameters);
                            break;
                        case ENRICH_EC_POI;
                            $service = app(EcPoiJobsServiceProvider::class);
                            $service->enrichJob($parameters);
                            break;
                        case UPDATE_GEOMIXER_TAXONOMY_WHERE;
                            $service = app(TaxonomyWhereJobsServiceProvider::class);
                            $service->updateJob($parameters);
                            break;
                        case UPDATE_UGC_TAXONOMY_WHERES;
                            $service = app(TaxonomyWhereJobsServiceProvider::class);
                            $service->updateWheresToFeatureJob($parameters);
                            break;
                        case GENERATE_MBTILES_SQUARE;
                            $service = app(MBTilesJobsServiceProvider::class);
                            $service->generateMBTilesSquareJob($parameters);
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
