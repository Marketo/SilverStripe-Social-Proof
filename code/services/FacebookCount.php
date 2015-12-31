<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Facebook interactions for a url
 */
class FacebookCount extends Controller implements SocialServiceInterface
{

    public $service = 'Facebook';
    public $statistic = array(
        'share_count',
        'like_count',
        'comment_count'
    );
    public $requestCount = 5;

    private function getFacebookCall($urls)
    {
        return 'https://api.facebook.com/method/fql.query' .
            '?query=select%20url,share_count,like_count,comment_count%20' .
            'from%20link_stat%20where%20url%20in("'
            . urlencode(implode('","', $urls)).'")';
    }

    public function getStatistics()
    {
        return $this->statistic;
    }

    public function processQueue($queueUrls)
    {
        $urls = array();
        $noEntries = count($queueUrls);
        $i = 0;
        $step = 0;
        try {
            foreach ($queueUrls as $url) {
                $i++;
                $step++;
                $urls[] = $url;
                if ($i == $this->requestCount || $step == $noEntries) {
                    $fileData = file_get_contents($this->getFacebookCall($urls));
                    if ($fileData === false) {
                        foreach ($urls as $errorUrl) {
                            $this->errorQueue[] = $url;
                        }
                    } else {
                        $xml = simplexml_load_string($fileData);

                        if ($xml->error_code || !$xml->link_stat) {
                            foreach ($urls as $errorUrl) {
                                $this->errorQueue[] = $url;
                                continue;
                            }
                        }
                        $results = $xml->link_stat;
                        $ids = array_flip($urls);
                        foreach ($results as $result) {
                            $resultUrl = (string)$result->url;
                            $statistics = URLStatistics::get()
                                ->filter(array(
                                    'URL' => $resultUrl,
                                    'Service' => $this->service,
                                    'Action' => $this->statistic
                                ));
                            if ($statistics && $statistics->exists()) {
                                foreach ($statistics as $statistic) {
                                    $statistic->Count = (int)$result->{$statistic->Action};
                                    $statistic->write();
                                }
                            } else {
                                foreach ($this->statistic as $countStat) {
                                    $statistic = URLStatistics::create();
                                    $statistic->URL = $resultUrl;
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
                                            'URL' => $resultUrl,
                                            'Service' => $this->service,
                                            'Action' => $statistic
                                        ))->first();
                                        ;
                                        if (!$stat || !$stat->exists()) {
                                            $stat = URLStatistics::create();
                                            $stat->URL = $resultUrl;
                                            $stat->Service = $this->service;
                                            $stat->Action = $statistic;
                                            $stat->Count = (int)$result->{$statistic};
                                            $stat->write();
                                        }
                                    }
                                }
                            }
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
