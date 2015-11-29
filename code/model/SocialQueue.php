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
        'Queued' => 'Boolean'
    );

    private static $has_one = array(
        'URL' => 'SocialURL'
    );

    private static $summary_fields = array(
        'Address',
        'Queued'
    );

    private static $defaults = array(
        'Queued' => 1
    );

    public function getaddress() {
        return $this->URL()->URL;
    }

    public static function queueURL($url) {
        $socialUrl = SocialURL::get()
            ->filter(array(
                'URL' => $url,
                'Active' => 1
            ));
        if ($socialUrl && $socialUrl->exists()) {
            $urlID = $socialUrl->first()->ID;
        } else {
            $socialUrl = SocialURL::create();
            $socialUrl->URL = $url;
            $socialUrl->Active = 1;
            $socialUrl->write();
            $urlID = $socialUrl->ID;
        }
        // check it is not already queued first as we may not need to fire off a curl request
        $queue = SocialQueue::get()
            ->filter(array(
                'URLID' => $urlID
            ))->first();
        if ($queue && $queue->exists()) {
            if ($queue->Queued == 1) {
                return true;
            } else {
                $queue->Queued = true;
                $queue->write();
                return true;
            }
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
            if (!$queue->exists()) {
                $queue = new SocialQueue();
                $queue->URLID = $urlID;
                $queue->write();
            }
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
