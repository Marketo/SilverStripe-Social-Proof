<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * Some basic testing for the various models
 */

class  SocialProofTest extends SapphireTest {

    private $testURL = 'http://www.marketo.com';
    private $services = array(
        'FacebookCount',
        'GooglePlusCount',
        'LinkedinCount',
        'TwitterCount'
        
    );

    public function setUp() {
        parent::setUp();

        SocialQueue::queueURL($this->testURL);

        // now setup a statistics for the URL
        foreach ($this->services as $service) {
            $countService = new $service();
            if (is_array($countService->statistic)) {
                foreach ($countService->statistic as $statistic) {
                    $stat = URLStatistics::create();
                    $stat->Service = $countService->service;
                    $stat->Action = $statistic;
                    $stat->Count = 50;
                    $stat->URL = $this->testURL;
                    $stat->write();
                }
            } else {
                $stat = URLStatistics::create();
                $stat->Service = $countService->service;
                $stat->Action = $countService->statistic;
                $stat->Count = 50;
                $stat->URL = $this->testURL;
                $stat->write();
            }
        }

    }

    public function testSocialQueue() {
        $socialQueue = SocialQueue::get()
            ->filter('Active',1)
            ->last();

        $this->assertEquals(
            $socialQueue->URLs,
            serialize(array($this->testURL))
        );
        $this->assertEquals($socialQueue->Active, 1);
    }

    public function testFacebookStat() {
        $stats = URLStatistics::get()
            ->filter(array(
                'Service' => 'Facebook'
            ));
        $facebookService = new FacebookCount();
        $statistics = $facebookService->getStatistics();
        foreach ($stats as $stat) {
            $this->assertEquals($stat->Service, 'Facebook');
            $this->assertArrayHasKey(
                $stat->Action,
                array_flip($statistics)
            );
            $this->assertEquals($stat->Count, 50);
            $this->assertEquals($stat->URL, $this->testURL);
        }
    }

    public function testGooglePlusStat() {
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Google'
            ))->first();
        $this->assertEquals($stat->Service, 'Google');
        $this->assertEquals($stat->Action, 'count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URL, $this->testURL);
    }

    public function testLinkedinStat() {
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Linkedin'
            ))->first();
        $this->assertEquals($stat->Service, 'Linkedin');
        $this->assertEquals($stat->Action, 'handle_count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URL, $this->testURL);
    }

    public function testTwitterStat() {
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Twitter'
            ))->first();
        $this->assertEquals($stat->Service, 'Twitter');
        $this->assertEquals($stat->Action, 'statuses_count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URL, $this->testURL);
    }
}
