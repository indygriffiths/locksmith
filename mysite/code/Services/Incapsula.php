<?php

/**
 * Class for managing Incapsula.
 *
 * You will need to define two environment variables for this class to function:
 *    INCAPSULA_API_KEY: API key created in the Incapsula web console
 *    INCAPSULA_API_ID: Numeric ID belonging to the API key
 */
class Incapsula
{
    /**
     * Gets all Incapsula sites.
     *
     * @return bool|mixed
     */
    public function Sites()
    {
        $results = $this->request('sites/list', [
            'page_size' => 50,
            'page_num' => 0,
        ]);
        $finalResults = $results->sites;

        $currentResultTalley = count($finalResults);
        $page = 1;

        while (50 === $currentResultTalley) {
            $newResults = $this->request('sites/list', [
                'page_size' => 50,
                'page_num' => $page,
            ]);

            $finalResults = array_merge($finalResults, $newResults->sites);

            $currentResultTalley = count($newResults->sites);
            ++$page;
        }

        return $finalResults;
    }

    /**
     * Requests a resource from the Incapsula API.
     *
     * @param string $url    URL to query
     * @param array  $params Array of request parameters to pass into the body
     *
     * @return bool|mixed
     */
    protected function request($url, $params = [])
    {
        if (!defined('INCAPSULA_API_KEY') ||
           !defined('INCAPSULA_API_ID')
        ) {
            throw new InvalidArgumentException('Incapsula API keys missing - request not sent');
        }

        $params['api_id'] = INCAPSULA_API_ID;
        $params['api_key'] = INCAPSULA_API_KEY;

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, 'https://my.incapsula.com/api/prov/v1/'.$url);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($params));

        $result = curl_exec($c);
        curl_close($c);

        SS_Log::log(sprintf('Received response from Incapsula for request submitted by %s: %s', Member::currentUserID(), $result), SS_Log::INFO);

        return json_decode($result);
    }
}
