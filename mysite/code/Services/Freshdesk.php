<?php

/**
 * Class for managing OpsGenie alerts.
 */
class Freshdesk
{
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;

    const STATUS_OPEN = 2;
    const STATUS_RESOLVED = 4;
    const STATUS_CLOSED = 5;

    /**
     * Creates a ticket with the specified properties.
     *
     * @param array $params
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function createTicket($params = [])
    {
        return $this->request('tickets', 'POST', $params);
    }

    /**
     * Updates a ticket with the specified properties.
     *
     * @param int   $id
     * @param array $params
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function updateTicket($id, $params = [])
    {
        return $this->request('tickets/'.$id, 'PUT', $params);
    }

    /**
     * Closes an ticket by marking it as resolved. An optional note can be added.
     *
     * @param int    $id
     * @param string $note
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function closeTicket($id, $note = null)
    {
        if ($note) {
            $this->addNote($id, $note);
        }

        return $this->updateTicket($id, [
            'status' => self::STATUS_RESOLVED,
        ]);
    }

    /**
     * Adds a note to the ticket.
     *
     * @param int    $id      Ticket ID
     * @param string $note
     * @param bool   $private If the note can't be seen by the customer
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function addNote($id, $note, $private = true)
    {
        $params = [
            'body' => $note,
            'private' => $private,
            'user_id' => FRESHDESK_USER_ID,
        ];

        return $this->request('tickets/'.$id.'/notes', 'POST', $params);
    }

    /**
     * @return bool True if the required environment variables are set
     */
    public static function IsAvailable()
    {
        return defined('FRESHDESK_API_KEY') &&
                defined('FRESHDESK_DOMAIN') &&
                defined('FRESHDESK_USER_ID');
    }

    /**
     * Returns a numeric priority for a string representation.
     *
     * @param $status
     *
     * @return string
     */
    public static function PriorityAsString($status)
    {
        switch ($status) {
            case self::PRIORITY_LOW:
                return 'Low';
            case self::PRIORITY_MEDIUM:
                return 'Medium';
            case self::PRIORITY_HIGH:
                return 'High';
            case self::PRIORITY_URGENT:
                return 'Urgent';
            default:
                return 'Unknown';
        }
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
        if (!self::IsAvailable()) {
            throw new InvalidArgumentException('Freshdesk environment variables missing - request not sent');
        }

        if ('GET' === $method && !empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, FRESHDESK_DOMAIN.'/api/v2/'.$url);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_USERPWD, FRESHDESK_API_KEY.':X');
        curl_setopt($c, CURLOPT_HTTPHEADER, [
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
        var_dump($result);
        curl_close($c);

        return json_decode($result);
    }
}
