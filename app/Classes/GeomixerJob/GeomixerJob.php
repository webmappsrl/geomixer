<?php

namespace App\Classes\GeomixerJob;

use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class GeomixerJob {

    protected GeohubServiceProvider $geohub;
    protected $id;
    protected $instance;
    protected $parameters;
    protected $hoqu;
    protected $errorMessage;

    /**
     * It sets all needed properties in order to perform the enrich job (get, enrich, put)
     * 
     *
     * @param array $job Returned by HoquServiceProvider::pull method with the following mandatory keys: id, job, instance, parameter
     * @param HoquServiceProvider $hoqu HoquServiceProvider instance
     */
    public function __construct(array $job,  HoquServiceProvider $hoqu) 
    {
        $this->id=$job['id'];
        $this->instance=$job['instance'];
        $this->parameters=json_decode($job['parameters'],TRUE);
        $this->hoqu=$hoqu;
    }

    // GETTERS (check and test purpose)
    public function getId() { return $this->id; }
    public function getInstance() { return $this->instance; }
    public function getParameters() { return $this->parameters; }
    public function getErrorMessage() { return $this->errorMessage; }

    abstract protected function get();
    abstract protected function enrich();
    abstract protected function put();

    /**
     * It performs main operation in order to enrich and upload data
     *
     * @return boolean
     */
    public function execute() {
        $job = get_class($this);
        Log::info("Class $job is EXECUTING (get,enrich,put)");
        Log::info("Class $job is GETTING DATA get()");
        try {
            $this->get();
        } catch (Exception $e) {
            $this->errorMessage=$e->getMessage();
            return FALSE;
        }
        Log::info("Class $job is ENRICHING DATA enrich()");
        try {
            $this->enrich();
        } catch (Exception $e) {
            $this->errorMessage=$e->getMessage();
            return FALSE;
        }
        Log::info("Class $job is PUTTING DATA put()");
        try {
            $this->put();
        } catch (Exception $e) {
            $this->errorMessage=$e->getMessage();
            return FALSE;
        }

        return TRUE;
    }

}