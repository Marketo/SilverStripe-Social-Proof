<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Twitter interactions for a url
 */
class TwitterCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Twitter';
    public $statistic = 'statuses_count';

    public function processQueue(){
        try {
            foreach ($this->queue as $entry) {
                $twitter = new SSTwitter();
                $reply = $twitter->search($entry['URL']);
                if ($reply->errors) {
                    $this->errorQueue[] = $entry['URL'];
                    continue;
                }
                $metadata = $reply->statuses;
                $count = intval($metadata[0]->user->statuses_count);
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
        } catch (Exception $e) {
            return 0;
        }
        return $this->errorQueue;
    }
}
