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

	public function __construct() {
		$this->currentStep = 0;
		$this->totalSteps = SocialQueue::get()
            ->filter('Queued',1)
            ->count();
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
		$queue = SocialQueue::get()
            ->filter('Queued',1);
        foreach ($queue as $entry) {
            $this->currentStep++;
            $entry->Queued = 0;
            $entry->write();
        }
		$this->completeJob();
		return;
	}

	/**
	 * Setups the next cron job
	 */
	protected function completeJob() {
		$this->isComplete = true;
		$nextgeneration = new UpdateSocialStatsJob();
		singleton('QueuedJobService')
            ->queueJob($nextgeneration, date('Y-m-d H:i:s', time() + self::$regenerate_time));
	}
}
