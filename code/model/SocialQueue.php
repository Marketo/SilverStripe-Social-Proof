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
        'Active' => 'Boolean',
        'URLs' => 'Text'
    );

    private static $summary_fields = array(
        'Address',
        'Active'
    );

    private static $defaults = array(
        'Active' => 1
    );

    public static function queueURL($url) {
        // are we locking down the domain
        $checkDomain = Config::inst()->get('SocialProofSettings', 'check_domain');
        if ($checkDomain) {
            $match = strstr($url, $checkDomain);
            if ($match === false) return;
        }
        $queuedUrls = array();
        // check it is not already queued first as we may not need to fire off a curl request
        $queue = SocialQueue::get()->filter('Active',1)->last();
        if ($queue && $queue->exists()) {
            $queuedUrls = (array)unserialize($queue->URLs);
            if (is_array($queuedUrls) && in_array($url, $queuedUrls)) {
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
            $queuedUrls = (array)$queuedUrls;
            $queuedUrls[] = $url;
            if (!$queue || !$queue->exists()) {
                $queue = new SocialQueue();
            }
            $queue->URLs = serialize($queuedUrls);
            $queue->write();
            return true;
        }
        return false;
    }
}
