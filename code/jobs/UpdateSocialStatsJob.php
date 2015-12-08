<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A job for processing a queue of Social media URLs
 */
class UpdateSocialStatsJob extends AbstractQueuedJob {
    /**
     * @var int
     */
    private static $regenerate_time = 300;
    private $URLs;

    public function __construct() {
        $this->URLs = array();
        $this->currentStep = 0;
        $queue = SocialQueue::get()
            ->filter('Active',1)
            ->last();
        if ($queue && $queue->exists()) {
            $this->URLs = (isset($queue->URLs)) 
            ? unserialize($queue->URLs)
            : array();
            $this->totalSteps = count($this->URLs);
            // stop any further urls being added to this queue
            $queue->Active = 0;
            $queue->write();
        }
    }

    public function getJobType() {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return 'A job for processing a queue of Social media URLs';
    }

    /**
     * Return a signature for this queued job
     *
     * @return string
     */
    public function getSignature() {
        return md5(get_class($this));
    }

    public function process() {
        $services = array();
        foreach (Config::inst()->get('UpdateSocialStatsJob', 'services') as $service) {
            $services[] = $service::create();
        }
        $requeue = array();
        foreach ($services as $service) {
            $errors = $service->processQueue($this->URLs);
            foreach ($errors as $error) {
                if (!in_array($error, $requeue)) {
                    $requeue[] = $error;
                }
            }
        }
        foreach ($requeue as $queue) {
            SocialQueue::queueURL($queue);
        }
        $this->completeJob();
        return;
    }

    /**
     * Setup the next cron job
     */
    protected function completeJob() {
        $this->isComplete = true;
        $nextgeneration = new UpdateSocialStatsJob();
        singleton('QueuedJobService')
            ->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
    }
}
