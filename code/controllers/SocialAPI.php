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

    private static $allow_cors = false;

    private static $allowed_domains = [];

    private static $domain_mapping = [];

    public function countsFor()
    {
        $response = $this->getResponse();
        $cors = $this->stat('allow_cors');
        $allowedDomains = $this->stat('allowed_domains');
        $requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : false;

        // handle this if is not array, i.e. if single string or if *
        if ($cors && $requestOrigin) {
            if (is_array($allowedDomains) && count($allowedDomains)) {
                foreach ($allowedDomains as $allowedDomain) {
                    if ($requestOrigin == $allowedDomain) {
                        $response->addHeader('Access-Control-Allow-Origin', $requestOrigin);
                        break;
                    } elseif (strpos($allowedDomain, '*') !== false) {
                        if (fnmatch($allowedDomain, $requestOrigin)) {
                            $response->addHeader('Access-Control-Allow-Origin', $requestOrigin);
                            break;
                        }
                    }
                }
            } elseif (is_string($allowedDomains)) {
                if ($requestOrigin === $allowedDomains) {
                    $response->addHeader('Access-Control-Allow-Origin', $requestOrigin);
                } elseif (strpos($allowedDomains, '*') !== false) {
                    if (fnmatch($allowedDomains, $requestOrigin)) {
                        $response->addHeader('Access-Control-Allow-Origin', $requestOrigin);
                    }
                }
            }
        }

        $urls = explode(',', $this->request->getVar('urls'));
        // queue all urls to be checked
        foreach ($urls as $url) {
            if ($url = $this->handleURL($url)) {
                SocialQueue::queue_url($url);
            }
        }

        $urlObjs = URLStatistics::get()
            ->filter(array(
                'URL' => $urls
            ));
        if (!$urlObjs->count()) {
            $response->setBody(json_encode(array()));

            return $response;
        }
        $results = array();
        // do we need to filter the results any further
        $service = $this->getRequest()->param('SERVICE');
        $filter = null;
        if ($service && $service == 'service') {
            $filter = $this->getRequest()->param('FILTER');
        }
        foreach ($urlObjs as $urlObj) {

            if (!isset($results['results'][$urlObj->URL]['Total'])) {
                $results['results'][$urlObj->URL]['Total'] = 0;
            }

            if ($filter) {
                if (strtoupper($urlObj->Service) == strtoupper($filter)) {
                    $results['results'][$urlObj->URL]['Total'] += $urlObj->Count;
                    $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                        $urlObj->Action => $urlObj->Count
                    );
                }
            } else {
                $results['results'][$urlObj->URL]['Total'] += $urlObj->Count;
                $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                    $urlObj->Action => $urlObj->Count
                );
            }
        }

        $response->setBody(json_encode($results));

        return $response;
    }

    public function handleURL($url)
    {
        $domain_mapping = $this->stat('domain_mapping');
        $urlParts = parse_url($url);

        if(count($domain_mapping)) foreach($domain_mapping as $fromDomain => $toDomain) {
            if(fnmatch($fromDomain, $urlParts['host'])) {
                $urlParts['host'] = $toDomain;
                break;
            }
        }

        $domains = $this->stat('allowed_domains');

        if(in_array($urlParts['host'], $domains)) {
            return $this->rebuildURL($urlParts);
        }else {
            foreach($domains as $domain) {
                if(fnmatch($urlParts['host'], $domain)) {
                    $this->rebuildURL($urlParts);
                }
            }
        }

        return false;
    }

    public function rebuildURL($parts) {
        $url = isset($parts['scheme']) ? "{$parts['scheme']}://" : 'http://';
        $url .= $parts['host'];
        $url .= isset($parts['path']) ? $parts['path'] : null;
        $url .= isset($parts['query']) ? "?{$parts['query']}" : null;

        return $url;
    }

}
