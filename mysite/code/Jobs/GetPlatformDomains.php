<?php

/**
 * Gets all domains from SSP and CWP every hour and add them to the site.
 */
class GetPlatformDomains implements CronTask
{
    use CronTaskUtilities;

    private $domains;

    /**
     * @return string
     */
    public function getSchedule()
    {
        return '0 * * * *';
    }

    /**
     * @throws ValidationException
     */
    public function process()
    {
        $this->domains = Domain::get()->column('Domain');

        $this->processCWPDomains();
    }

    /**
     * Gets all verified Lets Encrypt domains
     */
    public function processCWPDomains() {
        if(!defined('CWP_DASH_TOKEN') || !defined('CWP_DASH_EMAIL')) {
            $this->log('Skipping CWP as CWP_DASH_EMAIL or CWP_DASH_TOKEN is not defined');
            return;
        }

        $client = $this->createClient('dash.cwp.govt.nz');

        try {
            $response = $client->get('ledomains', [
                'auth' => [CWP_DASH_EMAIL, CWP_DASH_TOKEN]
            ]);

            $domains = json_decode($response->getBody());

            foreach($domains->data as $d) {
                if (in_array($d->attributes->name, $this->domains, true) || !$d->attributes->is_challenge_verified) {
                    continue;
                }

                $newDomain = Domain::create();
                $newDomain->Domain = $d->attributes->name;
                $newDomain->Source = 'CWP';
                $newDomain->SourceID = $d->id;
                $newDomain->write();

                $this->log('Added '.htmlspecialchars($d->attributes->name), SS_Log::INFO);
            }
        } catch(\Exception $e) {
            $this->log('Got exception trying to get CWP domains', SS_Log::WARN);
        }
    }

    /**
     * Creates a Guzzle client for the specific platform URL
     * @param string $url
     * @return \GuzzleHttp\Client
     */
    private function createClient($url) {
        return new \GuzzleHttp\Client([
            'base_uri' => 'https://'.$url.'/naut/',
            'headers' => [
                'X-Api-Version' => '2.0',
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ]
        ]);
    }
}
