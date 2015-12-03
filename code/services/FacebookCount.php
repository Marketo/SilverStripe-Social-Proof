<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Facebook interactions for a url
 */
class FacebookCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Facebook';
    public $statistic = array(
        'share_count',
        'like_count',
        'comment_count'
    );
    public $requestCount = 5;

	private function getFacebookCall($urls) {
        return 'https://api.facebook.com/method/fql.query' .
            '?query=select%20url,share_count,like_count,comment_count%20' .
            'from%20link_stat%20where%20url%20in("'
            . urlencode(implode('","', $urls)).'")';
    }

    public function getStatistics() {
        return $this->statistic;
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
                    if($fileData === FALSE) {
                        $this->errorQueue[] = $url;
                    } else {

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
                            $statistics = URLStatistics::get()
                                ->filter(array(
                                    'URLID' => $entry->URLID,
                                    'Service' => $this->service,
                                    'Action' => $this->statistic
                                ));
                            if ($statistics && $statistics->exists()) {
                                foreach ($statistics as $statistic) {
                                    $statistic->Count = (int)$result->{$statistic->Action};
                                    $statistic->write();
                                }
                            } else {
                                foreach($this->statistic as $countStat) {
                                    $statistic = URLStatistics::create();
                                    $statistic->URLID = $entry->URLID;
                                    $statistic->Service = $this->service;
                                    $statistic->Action = $countStat;
                                    $statistic->Count = (int)$result->{$statistic->Action};
                                    $statistic->write();
                                }
                            }
                            // sanity check what should be in the db and what is actually in it
                            if (count($this->statistic) != $statistics->count()) {
                                foreach ($this->statistic as $statistic) {
                                    if ($result->$statistic) {
                                        // do we have this in the db
                                        $stat = URLStatistics::get()
                                            ->filter(array(
                                            'URLID' => $entry->URLID,
                                            'Service' => $this->service,
                                            'Action' => $statistic
                                        ))->first();;
                                        if (!$stat || !$stat->exists()) {
                                            $stat = URLStatistics::create();
                                            $stat->URLID = $entry->URLID;
                                            $stat->Service = $this->service;
                                            $stat->Action = $statistic;
                                            $stat->Count = (int)$result->{$statistic};
                                            $stat->write();
                                        } 
                                    }
                                }
                            }
                            $entry->Queued = 0;
                            $entry->write();
                        }
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
