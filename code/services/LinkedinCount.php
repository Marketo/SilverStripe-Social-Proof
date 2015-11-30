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

    function getLinkedInCall() {
        return 'http://www.linkedin.com/countserv/count/share?url='
            . urlencode($this->entry['URL']);
    }
    function processQueue(){
        try {
            foreach ($this->queue as $entry) {
                $this->entry = $entry;
                $fileData = file_get_contents($this->getLinkedInCall());
                $output = str_replace(array('IN.Tags.Share.handleCount(',');'),'',trim($fileData));
                if($output !== FALSE) {
                    $json = json_decode($output);
                    unset($fileData); // free memory
                    $count = intval($json->count);
                    $id = $entry['ID'];
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
                    $statistic->Count = $count;
                    $statistic->write();
                    $entry->Queued = 0;
                    $entry->write();
                }
            }

        } catch (Exception $e) {
            return 0;
        }
    }
}
