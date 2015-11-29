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
            . urlencode($this->entry->URL);
    }
    function getCount(){
        try {
            $fileData = file_get_contents($this->getLinkedInCall());
            $output = str_replace(array('IN.Tags.Share.handleCount(',');'),'',trim($fileData));
            if($output === FALSE) return 0;

            $json = json_decode($output);
            unset($fileData); // free memory
            return intval($json->count);

        } catch (Exception $e) {
            return 0;
        }
    }
}
