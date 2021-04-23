<?php

namespace App\Providers;

use HttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

define('PULL_ENDPOINT', '/api/pull');
define('UPDATE_DONE_ENDPOINT', '/api/updateDone');
define('UPDATE_ERROR_ENDPOINT', '/api/updateError');

/**
 * This class provide a an interface to communicate with HOQU as a singleton service provider
 *
 * Class HoquServiceProvider
 *
 * @package App\Providers
 */
class HoquServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(HoquServiceProvider::class, function ($app) {
            return new HoquServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
    }

    /**
     * Return the headers needed to communicate with HOWU
     *
     * @return string[]
     *
     * @throws MissingMandatoryParametersException
     */
    private function _getHeaders(): array {
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
     * @param array  $payload  the payload
     *
     * @return false|resource
     */
    private function _getCurl(string $endpoint, array $payload) {
        $ch = curl_init(config('hoqu.base_url') . $endpoint);
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
    public function pull(array $jobs, array $acceptInstances = null): array {
        if (is_null($acceptInstances)) $acceptInstances = [''];

        Log::debug('Performing pull from HOQU:');
        Log::debug('  instances: ' . json_encode($acceptInstances));

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'task_available' => $jobs,
            'accept_instances' => json_encode($acceptInstances)
        ];

        $ch = $this->_getCurl(PULL_ENDPOINT, $payload);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException('Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        if ($code === 201)
            return [];
        else
            return json_decode($result, true);
    }

    /**
     * Perform a HOQU Update Done operation
     *
     * @param int    $jobId the job just completed
     * @param string $log   the log generated from the job execution
     *
     * @return int the api call result
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateDone(int $jobId, string $log = ''): int {
        Log::debug('Performing updateDone from HOQU');

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'log' => $log,
            'id_task' => $jobId
        ];

        $ch = $this->_getCurl(UPDATE_DONE_ENDPOINT, $payload);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException('Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return $code;
    }

    /**
     * Perform a HOQU Update Done operation
     *
     * @param int    $jobId    the job just completed
     * @param string $errorLog the error generated from the job execution
     * @param string $log      the log generated from the job execution
     *
     * @return mixed
     *
     * @throws MissingMandatoryParametersException
     * @throws HttpException
     */
    public function updateError(int $jobId, string $errorLog, string $log = '') {
        Log::debug('Performing updateError from HOQU');

        $payload = [
            'id_server' => config('hoqu.server_id'),
            'log' => $log,
            'error_log' => $errorLog,
            'id_task' => $jobId
        ];

        $ch = $this->_getCurl(UPDATE_ERROR_ENDPOINT, $payload);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException('Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return $code;
    }
}
