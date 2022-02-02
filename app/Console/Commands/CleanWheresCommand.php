<?php

namespace App\Console\Commands;

use App\Models\TaxonomyWhere;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanWheresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geomixer:clean_wheres';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It removes all wheres items that are not still present in geohub';

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
        // Loop on all items
        $items = TaxonomyWhere::all();
        if (count($items)>0) {
            Log::info("Checking wheres items");
            foreach($items as $item) {
                $code = $this->checkTaxonomy($item->id);
                if ($code==200) {
                    Log::info("Checking ITEM {$item->id} CODE: $code ... OK");
                } else if ($code==404) {
                    $item->delete();
                    Log::info("Checking ITEM {$item->id} CODE: $code ... item removed");
                } else {
                    Log::info("Checking ITEM {$item->id} CODE: $code ... WARNING! nothing done");
                }
            }
        } else {
            Log::info("No items present in taxonomy where table");
        }
        return 0;
    }

    public function checkTaxonomy(int $id): int
    {
        $url = config('geohub.base_url') . GET_TAXONOMY_WHERE_ENDPOINT . $id;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;

    }

}
