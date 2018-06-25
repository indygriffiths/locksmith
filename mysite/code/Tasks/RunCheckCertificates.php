<?php

/**
 * Checks all domains for their certificates
 */
class RunCheckCertificates extends BuildTask {

    protected $title = 'Check SSL certificates';

    protected $description = 'Runs the CronTask that connects to each domain and checks what certificate is loaded, if any, and adds it to the domain';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new CheckSSLCertificates();
        $cf->process();
    }
}
