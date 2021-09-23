<?php

namespace App\Console\Commands;

use App\Providers\HoquJobs\MBTilesJobsServiceProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class GenerateMbtilesItaly extends Command implements SignalableCommandInterface {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geomixer:generate_mbtiles_italy';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all the mbtiles in Italy using the new Webmapp MBTiles convention';
    private bool $interrupted;
    private MBTilesJobsServiceProvider $mbtilesJobsServiceProvider;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MBTilesJobsServiceProvider $mbtilesJobsServiceProvider) {
        parent::__construct();
        $this->interrupted = false;

        $this->mbtilesJobsServiceProvider = $mbtilesJobsServiceProvider;
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
        Log::channel('stdout')->info("Starting mbtiles generation");
        $zoomLevels = config('geomixer.hoqu.mbtiles.zoom_levels');

        foreach ($zoomLevels as $zoom => $config) {
            for ($x = $config['x'][0]; $x <= $config['x'][1]; $x++) {
                for ($y = $config['y'][0]; $y <= $config['y'][1]; $y++) {
                    $exists = Storage::disk('mbtiles')->exists("/raster/$zoom/$x/$y.mbtiles");
                    if (!$exists) {
                        Log::channel('stdout')->info("Generating $zoom/$x/$y.mbtiles...");
                        try {
                            //                            $mbtilesJobsServiceProvider->generateMBTilesSquareJob([
                            //                                'zoom' => $zoom,
                            //                                'x' => $x,
                            //                                'y' => $y
                            //                            ]);
                            Log::channel('stdout')->info("Package $zoom/$x/$y.mbtiles generated successfully");
                        } catch (Exception $e) {
                            Log::channel('stdout')->warning("Generation of $zoom/$x/$y.mbtiles encountered an error: " . $e->getMessage());
                            Log::channel('stdout')->warning("Package $zoom/$x/$y.mbtiles skipped");
                        }
                    } else
                        Log::channel('stdout')->info("Package $zoom/$x/$y.mbtiles already generated. Skipping");
                    if ($this->interrupted)
                        break;
                }
                if ($this->interrupted)
                    break;
            }
            if ($this->interrupted)
                break;
        }

        if ($this->interrupted)
            Log::channel('stdout')->warning("Package generation interrupted");
        else
            Log::channel('stdout')->info("Package generation completed!");

        return 0;
    }
}
