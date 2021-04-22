<?php

namespace App\Providers;

use HttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class HoquServiceProvider extends ServiceProvider {
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
        if (!config('hoqu.token.pull'))
            throw new MissingMandatoryParametersException('The pull token is needed to perform a pull from HOQU but none was provided. You can add it in the HOQU_PULL_TOKEN env variable');
        if (is_null($acceptInstances)) $acceptInstances = [''];
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . config('hoqu.token.pull'),
            'Content-Type:application/json'
        ];

        Log::debug('Performing pull from HOQU:');
        Log::debug('  instances: ' . json_encode($acceptInstances));

        $payload = [
            "id_server" => config('hoqu.server_id'),
            "task_available" => $jobs,
            "accept_instances" => json_encode($acceptInstances)
        ];

        $ch = curl_init(config('hoqu.base_url'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($code >= 400)
            throw new HttpException('Error ' . $code . ' calling ' . config('hoqu.base_url') . ': ' . $error);

        return json_decode($result, true);
    }
}
