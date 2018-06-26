<?php

class RunGetIncapsulaDomains extends BuildTask {

    protected $title = 'Add Domains from Incapsula';

    protected $description = 'Runs the CronTask that adds all sites from Incapsula and adds them as domains to the site';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new GetIncapsulaDomains();
        $cf->process();
    }
}
