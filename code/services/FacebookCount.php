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

    private function getFacebookCall($url)
    {
        return "http://graph.facebook.com/?id={$url}";
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

                    $fbUrl = $this->getFacebookCall($url);
                    $responseData = false;

                    if ($fbUrl) {
                        $responseData = json_decode(
                            file_get_contents($fbUrl)
                        );
                    }

                    if (!$responseData || !isset($responseData->share)) {
                        continue;
                    }

                    $shareData = $responseData->share;

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
