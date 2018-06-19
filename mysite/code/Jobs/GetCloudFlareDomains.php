<?php

/**
 * Gets all domains from CloudFlare every hour and adds them to the site
 */
class GetCloudFlareDomains implements CronTask {

    /**
     * @return string
     */
    public function getSchedule() {
        return "0 * * * *";
    }

    /**
     * @throws ValidationException
     */
    public function process() {
        // Get all existing domains
        $domains = Domain::get()->filter(['Source' => 'CloudFlare'])->column('SourceID');

        $cf = new CloudFlare();
        $zones = $cf->Zones();

        foreach ($zones as $z) {
            if (in_array($z->id, $domains)) {
                continue;
            }

            $newDomain = Domain::create();
            $newDomain->Domain = $z->name;
            $newDomain->Source = 'CloudFlare';
            $newDomain->SourceID = $z->id;
            $newDomain->write();

            echo 'Added '.htmlspecialchars($z->name).'<br>';
        }
    }
}
