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
        $socialURL = SocialURL::get()->first();

        // now setup a statistics for the URL
        foreach ($this->services as $service) {
            $countService = new $service();
            if (is_array($countService->statistic)) {
                foreach ($countService->statistic as $statistic) {
                    $stat = URLStatistics::create();
                    $stat->Service = $countService->service;
                    $stat->Action = $statistic;
                    $stat->Count = 50;
                    $stat->URLID = $socialURL->ID;
                    $stat->write();
                }
            } else {
                $stat = URLStatistics::create();
                $stat->Service = $countService->service;
                $stat->Action = $countService->statistic;
                $stat->Count = 50;
                $stat->URLID = $socialURL->ID;
                $stat->write();
            }
        }

    }

    public function testActiveSocialURL() {
        $socialURL = SocialURL::get()->first();

        $this->assertEquals($socialURL->Active, 1);
        $this->assertEquals($socialURL->URL, $this->testURL);
    }

    public function testSocialQueue() {
        $socialURL = SocialURL::get()->first();
        $socialQueue = SocialQueue::get()->first();

        $this->assertEquals($socialQueue->getAddress(), $socialURL->URL);
        $this->assertEquals($socialQueue->Queued, 1);
    }

    public function testFacebookStat() {
        $socialURL = SocialURL::get()->first();
        $stats = URLStatistics::get()
            ->filter(array(
                'Service' => 'Facebook'
            ));
        foreach ($stats as $stat) {
            $this->assertEquals($stat->Service, 'Facebook');
            $this->assertArrayHasKey(
                $stat->Action,
                array_flip(array('like_count', 'share_count'))
            );
            $this->assertEquals($stat->Count, 50);
            $this->assertEquals($stat->URLID, $socialURL->ID);
        }
    }

    public function testGooglePlusStat() {
        $socialURL = SocialURL::get()->first();
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Google'
            ))->first();
        $this->assertEquals($stat->Service, 'Google');
        $this->assertEquals($stat->Action, 'count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URLID, $socialURL->ID);
    }

    public function testLinkedinStat() {
        $socialURL = SocialURL::get()->first();
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Linkedin'
            ))->first();
        $this->assertEquals($stat->Service, 'Linkedin');
        $this->assertEquals($stat->Action, 'handle_count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URLID, $socialURL->ID);
    }

    public function testTwitterStat() {
        $socialURL = SocialURL::get()->first();
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => 'Twitter'
            ))->first();
        $this->assertEquals($stat->Service, 'Twitter');
        $this->assertEquals($stat->Action, 'statuses_count');
        $this->assertEquals($stat->Count, 50);
        $this->assertEquals($stat->URLID, $socialURL->ID);
    }
}
