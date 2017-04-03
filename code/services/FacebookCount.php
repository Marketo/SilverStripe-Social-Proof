<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Facebook interactions for a url
 */
class FacebookCount extends Controller implements SocialServiceInterface
{

    private static $api_key = false;

    public $service = 'Facebook';

    public $statistic = array(
        'share_count',
        'like_count',
        'comment_count'
    );

    public $requestCount = 50;

    private function getFacebookCall($urls)
    {
        $api_key = $this->stat('api_key');

        $urls = implode(',', $urls);
        $url = "http://graph.facebook.com/?ids={$urls}";

        if($api_key) {
            $url .= "&access_token={$api_key}";
        }

        return $url;
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
                    $fbUrl = $this->getFacebookCall($urls);
                    $responseData = false;

                    if ($fbUrl) {
                        $responseData = json_decode(
                            file_get_contents($fbUrl)
                        );
                    }

                    if (count($responseData)) {
                        foreach ($responseData as $urlData) {
                            if (!$urlData || !isset($urlData->share)) {
                                continue;
                            }

                            $shareData = $urlData->share;

                            foreach ($this->statistic as $statistic) {

                                $urlStatistics = URLStatistics::get()->filter([
                                    'URL' => $url,
                                    'Service' => $this->service,
                                    'Action' => $statistic
                                ]);

                                $statisticValue = isset($shareData->{$statistic}) ? $shareData->{$statistic} : 0;

                                if ($urlStatistics && $urlStatistics->exists()) {
                                    $urlStatistic = $urlStatistics->first();
                                    $urlStatistic->Count = (int)$statisticValue;
                                    $urlStatistic->write();
                                } else {
                                    $urlStatistic = URLStatistics::create();
                                    $urlStatistic->URL = $url;
                                    $urlStatistic->Service = $this->service;
                                    $urlStatistic->Action = $statistic;
                                    $urlStatistic->Count = (int)$statisticValue;
                                    $urlStatistic->write();
                                }
                            }
                        }
                    }

                    unset($responseData); // free memory
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
