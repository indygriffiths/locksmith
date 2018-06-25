<?php

/**
 * Goes through all domains, checks their certificate expiry, and creates or upgrades
 * OpsGenie alerts for the approaching expiration
 */
class CreateOpsGenieAlerts extends BaseCronTask {
    /**
     * @return string
     */
    public function getSchedule() {
        return "0/30 * * * *";
    }

    public function process() {
        $siteConfig = SiteConfig::current_site_config();
        if(!defined('OPSGENIE_API_KEY') || !$siteConfig->CreateOpsGenieAlert) {
            $this->log('OpsGenie not enabled, task not running', SS_Log::INFO);

            return;
        }

        $opsgenie = new OpsGenie();
        $domains = Domain::get()->filter(['Enabled' => 1]);

        $this->log('Number of existing domains: '.$domains->count(), SS_Log::INFO);

        $alertDays = [
            'P5' => $siteConfig->OpsGenieDaysUntilP5,
            'P4' => $siteConfig->OpsGenieDaysUntilP4,
            'P3' => $siteConfig->OpsGenieDaysUntilP3,
            'P2' => $siteConfig->OpsGenieDaysUntilP2,
            'P1' => $siteConfig->OpsGenieDaysUntilP1
        ];

        $startAlerting = max(array_values($alertDays));

        foreach($domains as $d) {
            if(!$d->CurrentCertificate()->exists()) {
                continue;
            }

            $cert = $d->CurrentCertificate();

            $this->log('Checking '.$d->Domain, SS_Log::INFO);
            $this->log('Days until expiration: '.$cert->DaysUntilExpiration, SS_Log::DEBUG);

            // Skip if we're outside the alerting threshold
            if($cert->DaysUntilExpiration > $startAlerting) {
                // If there is a current OpsGenie alert try and close it as the cert may have been updated
                if($d->OpsGenieID) {
                    $this->log('Closing existing OpsGenie alert '.$d->OpsGenieID, SS_Log::INFO);

                    $opsgenie->closeAlert($d->OpsGenieID, [
                        'source' => SiteConfig::current_site_config()->Title,
                        'note'   => 'Closing as the certificate has been updated'
                    ]);

                    $d->OpsGenieID = "";
                    $d->AlertPriority = "";
                    $d->write();
                }

                continue;
            }

            $priority = $this->closestNumber($cert->DaysUntilExpiration, $alertDays);
            $this->log('Current alert priority: '.$priority, SS_Log::DEBUG);

            // Create an OpsGenie alert if one isn't already made
            if(!$d->OpsGenieID) {
                $this->log('Creating OpsGenie alert', SS_Log::INFO);

                $alert = $opsgenie->createAlert([
                    'message'     => $d->Domain.' certificate expires '.$cert->ValidTo,
                    'description' => "The certificate for ".$d->Domain." will expire on ".$cert->ValidTo,
                    'source'      => SiteConfig::current_site_config()->Title,
                    'tags'        => ['ssl'],
                    "entity"      => $d->Domain,
                    'priority'    => $priority,
                    "details"     => [
                        'Domain'      => $d->ID,
                        'Certificate' => $cert->ID,
                        'Expiration'  => $cert->ValidTo,
                        'Serial'      => $cert->Serial,
                        'Fingerprint' => $cert->Fingerprint,
                    ]
                ]);

                $d->OpsGenieID = $alert->data->id;
                $d->AlertPriority = $priority;

                $d->write();
            } elseif($priority != $d->AlertPriority) {
                // Upgrade the priority if its different
                $this->log('Upgrading OpsGenie alert from '.$d->AlertPriority.' to '.$priority, SS_Log::INFO);
                $opsgenie->updateAlertPriority($d->OpsGenieID, $priority);

                $d->AlertPriority = $priority;
                $d->write();
            }
        }
    }
}