<?php

/**
 * Gets all domains from CloudFlare every hour and adds them to the site.
 */
class GetCloudFlareDomains implements CronTask
{
    use CronTaskUtilities;

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
        $domains = Domain::get()->column('Domain');

        $cf = new CloudFlare();
        $zones = $cf->Zones();

        foreach ($zones as $z) {
            if (in_array($z->name, $domains, true)) {
                continue;
            }

            $newDomain = Domain::create();
            $newDomain->Domain = $z->name;
            $newDomain->Source = 'CloudFlare';
            $newDomain->SourceID = $z->id;
            $newDomain->write();

            $this->log('Added '.htmlspecialchars($z->name), SS_Log::INFO);
        }
    }
}
