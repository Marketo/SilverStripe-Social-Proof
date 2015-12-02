<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A job for updateing article statistics
 */
class UpdateArticleStatisticsJob extends AbstractQueuedJob {
    /**
     * @var int
     * Rerun the job after a hour
     */
    private static $regenerate_time = 3600;

    public function __construct() {
        $this->currentStep = 0;
    }

    public function getJobType() {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return 'A job for updating the statistics for some articles';
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
        $this->completeJob();
        return;
    }

    /**
     * Setup the next cron job
     */
    protected function completeJob() {
        $this->isComplete = true;
        $nextgeneration = new UpdateArticleStatisticsJob();
        singleton('QueuedJobService')
            ->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
    }
}
