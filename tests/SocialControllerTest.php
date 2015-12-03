<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * Some controller testing
 */

class  SocialControllerTest extends FunctionalTest {

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
                unset($stat);
            }
        }

    }

    public function tearDown() {
        // remove db records otherwise we end up with multiple stats
        foreach (URLStatistics::get() as $stat) {
            $stat->delete();
        }
        foreach (SocialQueue::get() as $queue) {
            $queue->delete();
        }
        foreach (SocialURL::get() as $url) {
            $url->delete();
        }
        parent::tearDown();
    }

    public function testAPI() {
        $request = $this->get('api/countsfor?urls=' . $this->testURL);
        $this->assertEquals($request->getStatusCode(), 200);

        $body = $request->getBody();
        $jsonArray = json_decode($body, true);

        $results = $jsonArray['results']; 
        $www = $results[$this->testURL];

        foreach ($www['Facebook'] as $facebook) {
            foreach ($facebook as $key => $value) {
                $this->assertEquals($facebook[$key], 50);
            }
        }

        $google = $www['Google'][0];
        $this->assertEquals($google['count'], 50);

        $linkedin = $www['Linkedin'][0];
        $this->assertEquals($linkedin['handle_count'], 50);

        $twitter = $www['Twitter'][0];
        $this->assertEquals($twitter['statuses_count'], 50);

        // confirm the URL has been requeued
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);
        $this->assertEquals($socialQueue->getAddress(), $this->testURL);
    }

    public function testFacebookServiceAPI() {
        $request = $this->get('api/countsfor/service/facebook?urls=' . $this->testURL);
        $this->assertEquals($request->getStatusCode(), 200);

        $body = $request->getBody();
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);

        $jsonArray = json_decode($body, true);

        $results = $jsonArray['results']; 
        $www = $results[$this->testURL];
        $facebookService = new FacebookCount();
        $statistics = $facebookService->getStatistics();
        $expectedCount = (count($facebookService->getStatistics()) * 2) + 1;
        $this->assertEquals(count($www,true),$expectedCount);

        foreach ($www['Facebook'] as $facebook) {
            foreach ($facebook as $key => $value) {
                $this->assertEquals($facebook[$key], 50);
            }
        }

        // confirm the URL has been requeued
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);
        $this->assertEquals($socialQueue->getAddress(), $this->testURL);
    }

    public function testGoogleServiceAPI() {
        $request = $this->get('api/countsfor/service/google?urls=' . $this->testURL);
        $this->assertEquals($request->getStatusCode(), 200);

        $body = $request->getBody();
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);

        $jsonArray = json_decode($body, true);

        $results = $jsonArray['results']; 
        $www = $results[$this->testURL];
        $this->assertEquals(count($www,true),3);

        $facebook = $www['Google'][0];
        $this->assertEquals($facebook['count'], 50);

        // confirm the URL has been requeued
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);
        $this->assertEquals($socialQueue->getAddress(), $this->testURL);
    }

    public function testLinkedinServiceAPI() {
        $request = $this->get('api/countsfor/service/linkedin?urls=' . $this->testURL);
        $this->assertEquals($request->getStatusCode(), 200);

        $body = $request->getBody();
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);

        $jsonArray = json_decode($body, true);

        $results = $jsonArray['results']; 
        $www = $results[$this->testURL];
        $this->assertEquals(count($www,true),3);

        $facebook = $www['Linkedin'][0];
        $this->assertEquals($facebook['handle_count'], 50);

        // confirm the URL has been requeued
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);
        $this->assertEquals($socialQueue->getAddress(), $this->testURL);
    }

    public function testTwitterServiceAPI() {
        $request = $this->get('api/countsfor/service/twitter?urls=' . $this->testURL);
        $this->assertEquals($request->getStatusCode(), 200);

        $body = $request->getBody();
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);

        $jsonArray = json_decode($body, true);

        $results = $jsonArray['results']; 
        $www = $results[$this->testURL];
        $this->assertEquals(count($www,true),3);

        $facebook = $www['Twitter'][0];
        $this->assertEquals($facebook['statuses_count'], 50);

        // confirm the URL has been requeued
        $socialQueue = SocialQueue::get()->first();
        $this->assertEquals($socialQueue->Queued, 1);
        $this->assertEquals($socialQueue->getAddress(), $this->testURL);
    }
}
