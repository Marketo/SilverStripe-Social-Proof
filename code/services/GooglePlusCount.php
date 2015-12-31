<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Google Plus interactions for a url
 */
class GooglePlusCount extends Controller implements SocialServiceInterface
{

    public $service = 'Google';
    public $statistic = 'count';

    public function processQueue($queueUrls)
    {
        try {
            foreach ($queueUrls as $url) {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'
                    . $url .
                    '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                $curl_results = curl_exec($curl);

                if (curl_errno($curl)) {
                    $this->errorQueue[] = $url;
                    continue;
                }
                curl_close($curl);

                $json = json_decode($curl_results, true);

                if (isset($json['error'])) {
                    $this->errorQueue[] = $url;
                    continue;
                }

                $count = intval($json[0]['result']['metadata']['globalCounts']['count']);
                $statistic = URLStatistics::get()
                    ->filter(array(
                        'URL' => $url,
                        'Service' => $this->service,
                        'Action' => $this->statistic
                    ))->first();
                if (!$statistic || !$statistic->exists()) {
                    $statistic = URLStatistics::create();
                    $statistic->URL = $url;
                    $statistic->Service = $this->service;
                    $statistic->Action = $this->statistic;
                }
                $statistic->Count = $count;
                $statistic->write();
            }
        } catch (Exception $e) {
            return 0;
        }
        return $this->errorQueue;
    }
}
