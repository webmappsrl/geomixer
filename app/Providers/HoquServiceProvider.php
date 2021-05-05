<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

define('GET_ENDPOINT', '/api/taxonomy_where/{id}');
define('PULL_ENDPOINT', '/api/pull');
define('STORE_ENDPOINT', '/api/store');
define('UPDATE_DONE_ENDPOINT', '/api/updateDone');
define('UPDATE_ERROR_ENDPOINT', '/api/updateError');

/**
 * This class provide a an interface to communicate with HOQU as a singleton service provider
 *
 * Class HoquServiceProvider
 *
 * @package App\Providers
 */
class HoquServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(HoquServiceProvider::class, function ($app) {
            return new HoquServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Return the headers needed to communicate with HOWU
     *
     * @return string[]
     *
     * @throws MissingMandatoryParametersException
     */
    private function _getHeaders(): array
    {
        if (!config('hoqu.token'))
            throw new MissingMandatoryParametersException('A token with UPDATE and PULL permissions is needed to perform an HOQU operation but none was provided. You can add it in the HOQU_TOKEN env variable');

        return [
            'Accept: application/json',
            'Authorization: Bearer ' . config('hoqu.token'),
            'Content-Type:application/json'
        ];
    }

    /**
     * Create a curl request ready to be executed
     *
     * @param string $endpoint the HOQU endpoint
     * @param array $payload the payload
     * @param bool $isPut true if the curl should perform a put operation
     *
     * @return false|resource
     */
    private function _getCurl(string $endpoint, array $payload, bool $isPut = false)
    {
        $ch = curl_init(config('hoqu.base_url') . $endpoint);
        if ($isPut)
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_getHeaders());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        return $ch;
    }

    /**
     * Perform a HOQU Pull operation
     *
     * @param array|null $jobs
     * @param array|null $acceptInstances
     *
     * @return array the result of the hoqu pull operation
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function pull(array $jobs, array $acceptInstances = null): array
    {
        if (is_null($acceptInstances)) $acceptInstances = [config('hoqu.geohub_domain')];

        Log::debug('Performing pull from HOQU:');
        Log::debug('  instances: ' . json_encode($acceptInstances));

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'task_available' => $jobs,
            'accept_instances' => json_encode($acceptInstances)
        ];

        $ch = $this->_getCurl(PULL_ENDPOINT, $payload, true);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        if ($code === 201)
            return [];
        else
            return json_decode($result, true);
    }

    /**
     * Perform a HOQU Update Done operation
     *
     * @param int $jobId the job just completed
     * @param string $log the log generated from the job execution
     *
     * @return int the api call result
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateDone(int $jobId, string $log = ''): int
    {
        Log::debug('Performing updateDone from HOQU');

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'log' => $log,
            'id_task' => $jobId
        ];

        $ch = $this->_getCurl(UPDATE_DONE_ENDPOINT, $payload, true);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return $code;
    }

    /**
     * Perform a HOQU Update Done operation
     *
     * @param int $jobId the job just completed
     * @param string $errorLog the error generated from the job execution
     * @param string $log the log generated from the job execution
     *
     * @return mixed
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateError(int $jobId, string $errorLog, string $log = '')
    {
        Log::debug('Performing updateError from HOQU');

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'log' => $log,
            'error_log' => $errorLog,
            'id_task' => $jobId
        ];

        $ch = $this->_getCurl(UPDATE_ERROR_ENDPOINT, $payload, true);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return $code;
    }

    /**
     * Perform a store operation on HOQU
     *
     * @param string $job the job to store
     * @param array $params the job parameters
     *
     * @return int  the HTTP code
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */

    public function store(string $job, array $params): int
    {
        $instance = config('hoqu.geohub_domain');

        Log::debug('Performing store to HOQU:');

        $payload = [
            'instance' => $instance,
            'job' => $job,
            'parameters' => $params,
        ];

        $ch = $this->_getCurl(STORE_ENDPOINT, $payload);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException($code, 'Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return $code;
    }
}
