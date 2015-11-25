<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A controller class for handling API requests
 */

class SocialAPI extends Controller
{
    private static $allowed_actions = array(
        'countsfor'
    );

    private static $url_handlers = array(
        'countsfor/$SERVICE/$FILTER'
            => 'countsFor'
    );

    public function countsFor() {
        $urls = explode(',',$this->request->getVar('urls'));
        // queue all urls to be checked
        foreach ($urls as $url) {
            $result = SocialQueue::queueURL($url);
        }
        $urlObjs = SocialURL::get()
            ->filter(array(
                'URL' => $urls,
                'Active' => 1
            ));
        if (!$urlObjs->count()) {
            return json_encode(array());
        }
        $result = array();
        // do we need to filter the results any further
        $service = $this->getRequest()->param('SERVICE');
        if ($service && $service == 'service') {
            $filter = $this->getRequest()->param('FILTER');
        }
        foreach ($urlObjs as $urlObj) {
            foreach ($urlObj->Statistics() as $statistic) {
                if ($filter) {
                    if (strtoupper($statistic->Service) == strtoupper($filter)) {
                        $results['results'][$urlObj->URL][$statistic->Service][] = array(
                            $statistic->Action => $statistic->Count
                        );
                    }
                } else {
                    $results['results'][$urlObj->URL][$statistic->Service][] = array(
                        $statistic->Action => $statistic->Count
                    );
                }
            }
        }
        return json_encode($results);
    }
}
