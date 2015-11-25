<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A data class representing a queue of social media urls to be processed
 */

class SocialQueue extends DataObject
{
    private static $singular_name = 'Social Queue';
    private static $plural_name = 'Social Queue';

    private static $db = array(
        'URL' => 'Varchar(1024)',
        'Queued' => 'Boolean'
    );

    private static $summary_fields = array(
        'URL',
        'Queued',
        'Created'
    );

    private static $defaults = array(
        'Queued' => 1
    );

    public function getCreated() {
        return date('D M Y h:m:i', strtotime($this->original['Created']));
    }

    public static function queueURL($url) {
        // check it is not already queued first as we may not need to fire off a curl request
        $queue = SocialQueue::get()
            ->filter(array(
                'URL' => $url,
                'Queued' => 1
            ));
        if ($queue && $queue->exists()) {
            return true;
        }
        // check if this is a valid url first of no point queuing something like foobar
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            $response = curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            if ($httpCode < 200 || $httpCode > 302) {
                return $httpCode;
            }
            $queue = new SocialQueue();
            $queue->URL = $url;
            $queue->write();
            return true;
        }
        return false;
    }
}

class SocialQueueAdmin extends ModelAdmin {
    private static $managed_models = array(
        'SocialQueue'
    );

    private static $url_segment = 'social-queue-admin';

    private static $menu_title = 'Social Queue';

}
