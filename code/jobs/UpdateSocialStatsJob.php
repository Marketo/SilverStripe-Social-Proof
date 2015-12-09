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

    public function __construct($initialise = false) {
        
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
	
	public function setup() {
		$this->currentStep = 0;
		$allUrls = array();
		$queues = SocialQueue::get()->filter('Active', 1);
		foreach ($queues as $queue) {
			// stop any further urls being added to this queue
			$queue->Active = 0;
			$queue->write();
			
			// reload to be sure
			$queue = SocialQueue::get()->byID($queue->ID);

			$urls = (isset($queue->URLs)) 
				? json_decode($queue->URLs, true)
				: array();

			if (!$urls) {
				$urls = array();
			}
			$allUrls += $urls;
		}

		array_walk($allUrls, function (&$value) {
			$value = rtrim($value, '/');
		});
		$this->URLs = $allUrls;
		$this->totalSteps = count($this->URLs);
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
		$this->currentStep = count($this->URLs);
        $this->completeJob();
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
