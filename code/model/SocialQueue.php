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

    public static function queue_url($url)
    {
        $queuedUrls = array();
        // get the latest queue and add the URL to the queue
        $queue = SocialQueue::get()->filter('Active', 1)->last();

        if ($queue && $queue->exists()) {
            $queuedUrls = (array)json_decode($queue->URLs, true);
        }

        $queuedUrls = (array)$queuedUrls;
        $queuedUrls[] = $url;
        $queuedUrls = array_unique($queuedUrls);

        if (!$queue || !$queue->exists()) {
            $queue = new SocialQueue();
        }

        $queue->URLs = json_encode($queuedUrls, true);
        $queue->write();
    }
}
