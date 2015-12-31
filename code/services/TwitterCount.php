<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A service to retrieve Twitter interactions for a url
 */
class TwitterCount extends Controller implements SocialServiceInterface
{

    public $service = 'Twitter';
    public $statistic = 'statuses_count';

    public function processQueue($queueUrls)
    {
        try {
            foreach ($queueUrls as $url) {
                $twitter = new SSTwitter();
                $reply = $twitter->search($url);
                if ($reply->errors) {
                    $this->errorQueue[] = $url;
                    continue;
                }
                $metadata = $reply->statuses;
                $count = intval($metadata[0]->user->statuses_count);
                $id = $entry['ID'];
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
        } catch (Exception $e) {
            return 0;
        }
        return $this->errorQueue;
    }
}
