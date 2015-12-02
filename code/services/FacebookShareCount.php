<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Facebook interactions for a url
 */
class FacebookShareCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Facebook';
    public $statistic = 'share_count';
    public $requestCount = 5;

	private function getFacebookCall($urls) {
        return 'https://api.facebook.com/method/fql.query' .
            '?query=select%20url,share_count%20from%20link_stat%20where%20url%20in("'
            . urlencode(implode('","', $urls)).'")';
    }

    public function processQueue(){
        $i = 0;
        $step = 0;
        $urls = array();
        $noEntries = count($this->queue);
        try {
            foreach ($this->queue as $entry) {
                $i++;
                $step++;
                $urls[$entry['ID']] = $entry['URL'];
                if ($i == $this->requestCount || $step == $noEntries) {
                    $fileData = file_get_contents($this->getFacebookCall($urls));
                    if($fileData === FALSE) return 0;

                    $xml = simplexml_load_string($fileData);

                    if ($xml->error_code || !$xml->link_stat) {
                        foreach ($urls as $url) {
                            $this->errorQueue[] = $url;
                            continue;
                        }
                    }
                    $results = $xml->link_stat;
                    $ids = array_flip($urls);
                    foreach ($results as $result) {
                        $url = (string)$result->url;
                        $id = $ids[$url];
                        $entry = SocialQueue::get_by_id('SocialQueue',$id);
                        $statistic = URLStatistics::get()
                            ->filter(array(
                                'URLID' => $entry->URLID,
                                'Service' => $this->service,
                                'Action' => $this->statistic
                            ))->first();
                        if (!$statistic || !$statistic->exists()) {
                            $statistic = URLStatistics::create();
                            $statistic->URLID = $entry->URLID;
                            $statistic->Service = $this->service;
                            $statistic->Action = $this->statistic;
                        }
                        $statistic->Count = (int)$result->{$this->statistic};
                        $statistic->write();
                        $entry->Queued = 0;
                        $entry->write();
                        
                    }

                    unset($fileData); // free memory
                    $i = 0;
                    $urls = array();
                }
            }

        } catch (Exception $e) {
            return 0;
        }
        return $this->errorQueue;
    }
}
