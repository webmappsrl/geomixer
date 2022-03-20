<?php

namespace App\Classes\GeomixerJob;

use App\Providers\GeohubServiceProvider;
use App\Providers\HoquServiceProvider;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\InvalidParameterException;

abstract class GeomixerJob {

    // SERVICES
    protected $geohub;
    protected $hoqu;

    // JOB PROPERTIES
    protected $id;
    protected $instance;
    protected $parameters;
    protected $errorMessage;

    // JOB DATA
    protected $inputData;
    protected $outputData;

    /**
     * It sets all needed properties in order to perform the enrich job (get, enrich, put)
     * 
     *
     * @param array $job Returned by HoquServiceProvider::pull method with the following mandatory keys: id, job, instance, parameter
     * @param HoquServiceProvider $hoqu HoquServiceProvider instance
     */
    public function __construct(array $job,  $hoqu, $geohub) 
    {
        // check parameter job: camel case it and check if is the 
        $jobFromParameter = str_replace('_', '', ucwords($job['job'], '_')).'GeomixerJob';
        if($jobFromParameter != (new \ReflectionClass($this))->getShortName()) {
            throw new InvalidParameterException('job parameter not VALID: expected->' . get_class($this) . ', actual->'.$jobFromParameter);
        }
    
        $this->id=$job['id'];
        $this->instance=$job['instance'];
        $this->parameters=json_decode($job['parameters'],TRUE);

        $this->hoqu=$hoqu;
        $this->geohub = $geohub;
    }

    // GETTERS (check and test purpose)
    public function getId() { return $this->id; }
    public function getInstance() { return $this->instance; }
    public function getParameters() { return $this->parameters; }
    public function getInputData() { return $this->inputData; }
    public function getOutputData() { return $this->outputData; }
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
            Log::error("ERROR ON GETTING DATA: ".$e->getMessage());
            return FALSE;
        }

        Log::info("Class $job is ENRICHING DATA enrich()");
        try {
            $this->enrich();
        } catch (Exception $e) {
            $this->errorMessage=$e->getMessage();
            Log::error("ERROR ON ENRICHING DATA: ".$e->getMessage());
            return FALSE;
        }

        Log::info("Class $job is PUTTING DATA put()");
        try {
            $this->put();
        } catch (Exception $e) {
            $this->errorMessage=$e->getMessage();
            Log::error("ERROR ON PUTTING DATA: ".$e->getMessage());
            return FALSE;
        }

        return TRUE;
    }

}