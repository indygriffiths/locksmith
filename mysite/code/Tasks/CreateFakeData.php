<?php

/**
 * Class for managing CreateFakeData
 * @package ${PACKAGE}
 */
class CreateFakeData extends BuildTask {

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request) {
        DB::query("DELETE FROM Domain WHERE Source = 'Incapsula'");

        for($i = 1; $i <= 30; $i++) {
            $d = new Domain();
            $d->Domain = "d$i.com";
            $d->Source = "Incapsula";
            $d->write();

            $newCert = new Certificate();
            $newCert->DomainID = $d->ID;
            $newCert->Name = "Test Cert $i";
            $newCert->ValidFrom = date('Y-m-d H:i:s');
            $newCert->ValidTo = date('Y-m-d H:i:s', strtotime("+".$i." days"));
            $newCert->write();
        }
    }
}