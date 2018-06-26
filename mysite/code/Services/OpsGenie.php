<?php

/**
 * Class for managing OpsGenie alerts.
 */
class OpsGenie
{
    /**
     * Gets the current status of a request.
     *
     * @param string $id
     *
     * @return bool|mixed
     */
    public function getRequestStatus($id)
    {
        return $this->request('alerts/requests/'.$id, 'GET');
    }

    /**
     * Gets all alerts.
     *
     * @param array $params
     *
     * @return bool|mixed
     */
    public function getAlerts($params = [])
    {
        return $this->request('alerts', 'GET', $params);
    }

    /**
     * Creates an alert.
     *
     * @param array $params
     *
     * @return bool|mixed
     */
    public function createAlert($params = [])
    {
        $alert = $this->request('alerts', 'POST', $params);

        $result = null;
        $count = 0;
        while (null === $result && $count <= 20) {
            $newRequest = $this->getRequestStatus($alert->requestId);

            if (isset($newRequest->data->alertId)) {
                $result = $this->getAlert($newRequest->data->alertId);
            }

            ++$count;
        }

        return $result;
    }

    /**
     * Gets an alert.
     *
     * @param string $id
     *
     * @return bool|mixed
     */
    public function getAlert($id)
    {
        return $this->request('alerts/'.$id);
    }

    /**
     * Closes an alert.
     *
     * @param string $id
     * @param array  $params
     *
     * @return bool|mixed
     */
    public function closeAlert($id, $params = [])
    {
        return $this->request('alerts/'.$id.'/close', 'POST', $params);
    }

    /**
     * Update an alerts message.
     *
     * @param string $id
     * @param string $message
     *
     * @return bool|mixed
     */
    public function updateAlertMessage($id, $message)
    {
        return $this->request('alerts/'.$id.'/message', 'PUT', [
            'message' => $message,
        ]);
    }

    /**
     * Update an alerts priority.
     *
     * @param string $id
     * @param string $priority
     *
     * @return bool|mixed
     */
    public function updateAlertPriority($id, $priority)
    {
        return $this->request('alerts/'.$id.'/priority', 'PUT', [
            'priority' => $priority,
        ]);
    }

    /**
     * Requests a resource from the OpsGenie API.
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
        if (!defined('OPSGENIE_API_KEY')) {
            throw new InvalidArgumentException('OpsGenie API keys missing - request not sent');
        }

        if ('GET' === $method && !empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, 'https://api.opsgenie.com/v2/'.$url);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'Authorization:GenieKey '.OPSGENIE_API_KEY,
            'Content-Type:application/json',
        ]);

        if ('POST' === $method) {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ('GET' !== $method) {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $result = curl_exec($c);
        curl_close($c);

        return json_decode($result);
    }
}
