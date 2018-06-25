<?php

class RunGetCloudFlareDomains extends BuildTask {

    protected $title = 'Add Domains from CloudFlare';

    protected $description = 'Runs the CronTask that adds all zones from CloudFlare and adds them as domains to the site';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new GetCloudFlareDomains();
        $cf->process();
    }
}
