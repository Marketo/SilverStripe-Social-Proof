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
    private $queue;

    public function __construct() {
        $this->currentStep = 0;
        $queue = SocialQueue::get()
            ->filter('Active',1)
            ->last();
        $this->totalSteps = count(unserialize($queue->URLs));
        $this->queue = $queue;
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
            $errors = $service->processQueue();
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
        $this->queue->Active = 0;
        $this->queue->write();
        $this->isComplete = true;
        $nextgeneration = new UpdateSocialStatsJob();
        singleton('QueuedJobService')
            ->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
    }
}
