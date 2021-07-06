<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geomixer:import_dem
                            {name : The name of the dem SQL file that must be imported, check https://tiles.webmapp.it/geodata/dem to find available name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data data from https://tiles.webmapp.it/geodata/dem into dem postgis table.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = base_path("geodata/dem/$name");

        if (!file_exists($path)) {
            $url = "https://tiles.webmapp.it/geodata/dem/$name";
            Log::notice("File $name does not exists: try to download $url.");
            // DOWNLOAD FROM https://tiles.webmapp.it/geodata/dem/$name
            if (!file_exists(base_path('geodata/dem'))) {
                Log::notice('Creating dir geodata/dem');
                exec("mkdir -p " . base_path('geodata/dem'));
            }
            Log::notice("Downloading: from $url to $path");
            file_put_contents($path, file_get_contents($url));

            // IMPORT
            $this->importIntoPostgis($path);
        } else {
            $this->importIntoPostgis($path);
        }
        return 0;
    }

    private function importIntoPostgis($path)
    {
        Log::notice("importing $path to DB");
        $cmd = '';
        if (!empty(config('database.connections.pgsql.password'))) {
            $pwd = config('database.connections.pgsql.password');
            $cmd = "PGPASSWORD='$pwd';";
        }
        $host = config('database.connections.pgsql.host');
        $dbname = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        if (!empty($username)) {
            $username = "-U $username";
        }
        $cmd = "$cmd psql --quiet -h $host -d $dbname $username < $path 2> /dev/null";
        Log::info("CMD: $cmd");
        $out = exec($cmd);
    }
}
