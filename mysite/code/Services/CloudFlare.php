<?php

/**
 * Class for managing CloudFlare zones.
 *
 * You will need to define two environment variables for this class to function:
 *    CLOUDFLARE_API_KEY: API key
 *    CLOUDFLARE_USER_EMAIL: Email address for the user
 */
class CloudFlare
{
    /**
     * Gets all CloudFlare zones.
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function Zones()
    {
        return $this->request('zones');
    }

    /**
     * Requests a resource from the CloudFlare API.
     *
     * @param string $url    URL to query
     * @param string $method
     * @param array  $params Array of request parameters to pass into the body
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    protected function request($url, $method = 'GET', $params = [])
    {
        $results = $this->make_request($url, $method, $params);
        $finalResults = $results->result;

        $page = $results->result_info->page;

        while ($results->result_info->total_pages > 1 && $page <= $results->result_info->total_pages) {
            $newResults = $this->make_request($url, $method, $params);

            $finalResults = array_merge($finalResults, $newResults->results);
            ++$page;
        }

        return $finalResults;
    }

    /**
     * Requests a resource from the CloudFlare API.
     *
     * @param string $url    URL to query
     * @param string $method
     * @param array  $params Array of request parameters to pass into the body
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    protected function make_request($url, $method = 'GET', $params = [])
    {
        if (!defined('CLOUDFLARE_API_KEY') ||
            !defined('CLOUDFLARE_USER_EMAIL')
        ) {
            throw new InvalidArgumentException('CloudFlare API keys missing - request not sent');
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/'.$url);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'X-Auth-Key:'.CLOUDFLARE_API_KEY,
            'X-Auth-Email:'.CLOUDFLARE_USER_EMAIL,
        ]);

        if ('POST' === $method) {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $result = curl_exec($c);
        curl_close($c);

        $result = json_decode($result);

        if (!$result->success) {
            throw new \Exception('Failed to request: '.$result->error);
        }

        return $result;
    }
}
