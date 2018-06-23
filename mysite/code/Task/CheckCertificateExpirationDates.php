<?php

/**
 * Class for managing CheckCertificateExpirationDates
 */
class CheckCertificateExpirationDates extends BuildTask {

    protected $title = 'Check Certificate Expiration Dates';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new CheckCertificateExpiration();
        $cf->process();
    }
}