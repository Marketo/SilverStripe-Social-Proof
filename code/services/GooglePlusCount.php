<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Google Plus interactions for a url
 */
class GooglePlusCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Google';
    public $statistic = 'count';

    function getCount(){
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'
                . $this->entry->URL .
                '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            $curl_results = curl_exec ($curl);

            if(curl_errno($curl)) return 0;
            curl_close ($curl);

            $json = json_decode($curl_results, true);

            return intval( $json[0]['result']['metadata']['globalCounts']['count'] );

        } catch (Exception $e) {
            return 0;
        }
    }
}
