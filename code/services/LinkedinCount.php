<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Linkedin interactions for a url
 */
class LinkedinCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Linkedin';
    public $statistic = 'handle_count';

    function getLinkedInCall($url='') {
        return 'http://www.linkedin.com/countserv/count/share?url='
            . urlencode($url);
    }
    function processQueue(){
        $queue = SocialQueue::get()->filter('Active',1)->last();
        $queueUrls = (array)unserialize($queue->URLs);
        try {
            foreach ($queueUrls as $url) {
                $fileData = file_get_contents($this->getLinkedInCall($url));
                if ($fileData === FALSE) {
                    $this->errorQueue[] = $url;
                    continue;
                }
                $output = str_replace(array('IN.Tags.Share.handleCount(',');'),'',trim($fileData));
                if($output !== FALSE) {
                    $json = json_decode($output);
                    unset($fileData); // free memory
                    $count = intval($json->count);
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
            }

        } catch (Exception $e) {
            return 0;
        }
        return $this->errorQueue;
    }
}
