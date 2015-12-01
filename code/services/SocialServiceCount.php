<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A base service for extending other services from
 */
class SocialServiceCount extends Controller
{
    public $entry;
    public $service;
    public $statistic;
    public $queue;
    public $errorQueue;

    public function setStatistic($count=0) {
        $stat = URLStatistics::get()
            ->filter(array(
                'Service' => $this->service,
                'Action' => $this->statistic,
                'URLID' => $this->entry->ID
            ));
        if ($stat && $stat->exists()) {
            $stat = $stat->first();
            if ($count > $stat->Count) {
                $stat->Count = $count;
                $stat->write();
            }
        } else {
            $stat = URLStatistics::create();
            $stat->Service = $this->service;
            $stat->Action = $this->statistic;
            $stat->Count = $count;
            $stat->URLID = $this->entry->ID;
            $stat->write();
        }
        return true;
    }

    public function queueEntry($entry) {
        $this->queue[] = array(
            'ID' => $entry->ID,
            'URL' => $entry->URL()->URL
        );
    }
}
