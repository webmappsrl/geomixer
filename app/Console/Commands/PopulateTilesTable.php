<?php

namespace App\Console\Commands;

use App\Models\Tiles;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PopulateTilesTable extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geomixer:populate_tiles_table {--zoom= : The zoom to generate}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the tiles table with all the mbtiles coordinates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $values = [];
        $zoomLevels = config('geomixer.hoqu.mbtiles.zoom_levels');
        $zoomParameter = $this->option('zoom');
        Tiles::truncate();

        if (isset($zoomParameter) && !empty($zoomParameter)) {
            try {
                $zoomParameter = explode(',', $zoomParameter);
                $zoomParameter = array_map('intval', $zoomParameter);
            } catch (Exception $e) {
                $zoomParameter = [];
            }
        } else $zoomParameter = [];

        foreach ($zoomLevels as $zoom => $config) {
            if (count($zoomParameter) === 0 || in_array($zoom, $zoomParameter)) {
                for ($x = $config['x'][0]; $x <= $config['x'][1]; $x++) {
                    for ($y = $config['y'][0]; $y <= $config['y'][1]; $y++) {
                        $values[] = [
                            'z' => $zoom,
                            'x' => $x,
                            'y' => $y,
                        ];
                        if (count($values) > 10000) {
                            // The batch is done to prevent php memory problems
                            Tiles::insert($values);
                            $values = [];
                            Log::channel('stdout')->info('Added batch');
                        }
                    }
                }
            }
        }

        Tiles::insert($values);
        Log::channel('stdout')->info('Added last batch');

        return 0;
    }
}
