<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service for facebook interactions for a page
 */
class FacebookCount extends SocialServiceCount implements SocialServiceInterface {

    public $entry;
    public $service = 'Facebook';
    public $statistic = 'like_count';

	private function getFacebookCall() {
        return 'https://api.facebook.com/method/fql.query' .
            '?query=select%20%20like_count%20from%20link_stat%20where%20url=%22'
            . urlencode($this->entry->URL).'%22';
    }

    public function getCount(){
        try {
            $fileData = file_get_contents($this->getFacebookCall());

            if($fileData === FALSE) return 0;

            $xml = simplexml_load_string($fileData);

            if ($xml->error_code || !$xml->link_stat) {
                return false;
            }
            $count = $xml->link_stat->{$this->statistic};

            unset($fileData); // free memory

            return intval($count);

        } catch (Exception $e) {
            return 0;
        }

    }
}
