<?php

/**
 * Gets all domains from Incapsula every hour and adds them to the site
 */
class GetIncapsulaDomains implements CronTask {
    use CronTaskUtilities;

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
        $domains = Domain::get()->filter(['Source' => 'Incapsula'])->column('SourceID');

        $cf = new Incapsula();
        $zones = $cf->Sites();

        foreach ($zones as $z) {
            if (in_array($z->site_id, $domains)) {
                continue;
            }

            $newDomain = Domain::create();
            $newDomain->Domain = $z->domain;
            $newDomain->Source = 'Incapsula';
            $newDomain->SourceID = $z->site_id;
            $newDomain->write();

            $this->log("Added ".htmlspecialchars($z->domain), SS_Log::INFO);
        }
    }
}
