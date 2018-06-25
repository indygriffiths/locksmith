<?php

class RunCreateOpsGenieAlerts extends BuildTask {

    protected $title = 'Create OpsGenie Alerts';

    protected $description = 'Runs the CronTask that will create OpsGenie alerts for expiring domains, update the priority of existing alerts, or close alerts for domains with new certificates';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new CreateOpsGenieAlerts();
        $cf->process();
    }
}