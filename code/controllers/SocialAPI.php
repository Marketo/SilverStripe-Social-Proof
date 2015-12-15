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
        $urlObjs = URLStatistics::get()
            ->filter(array(
                'URL' => $urls
            ));
        if (!$urlObjs->count()) {
            return json_encode(array());
        }
        $results = array();
        // do we need to filter the results any further
        $service = $this->getRequest()->param('SERVICE');
        $filter = null;
        if ($service && $service == 'service') {
            $filter = $this->getRequest()->param('FILTER');
        }
        foreach ($urlObjs as $urlObj) {
            if ($filter) {
                if (strtoupper($urlObj->Service) == strtoupper($filter)) {
                    $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                        $urlObj->Action => $urlObj->Count
                    );
                }
            } else {
                $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                    $urlObj->Action => $urlObj->Count
                );
            }
        }
        $cors = Config::inst()->get('SocialAPI', 'CORS');
        if ($cors) {
	        $this->response->addHeader('Access-Control-Allow-Origin', '*');
        }
        return json_encode($results);
    }
}
