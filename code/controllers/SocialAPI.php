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
        'countsfor'
            => 'countsfor'
    );

    public function countsfor() {
        $urls = explode(',',$this->request->getVar('pageUrl'));
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
        foreach ($urlObjs as $urlObj) {
            foreach ($urlObj->Statistics() as $statistic) {
                $results['results'][$urlObj->URL][$statistic->Service][] = array(
                    $statistic->Action => $statistic->Count
                );
            }
        }
        return json_encode($results);
    }
}
