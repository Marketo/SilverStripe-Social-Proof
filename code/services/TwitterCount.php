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

    public function getCount(){
        try {
            $twitter = new SSTwitter();
            $reply = $twitter->search($this->entry->URL);
            $metadata = $reply->statuses;
            return intval($metadata[0]->user->statuses_count);
        } catch (Exception $e) {
            return 0;
        }

    }
}
