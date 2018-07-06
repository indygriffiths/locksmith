<?php

/**
 * Goes through all domains, checks their certificate expiry, and creates or upgrades
 * Freshdesk tickets for the approaching expiration.
 */
class CreateFreshdeskTickets implements CronTask
{
    use CronTaskUtilities;

    /**
     * @return string
     */
    public function getSchedule()
    {
        return '0/30 * * * *';
    }

    public function process()
    {
        $siteConfig = SiteConfig::current_site_config();
        if (!Freshdesk::IsAvailable() || !$siteConfig->CreateFreshdeskTicket) {
            $this->log('Freshdesk not enabled, task not running', SS_Log::INFO);

            return;
        }

        if (!$siteConfig->FreshdeskGroupID) {
            $this->log('FreshdeskGroupId is empty or not numeric, task not running', SS_Log::INFO);

            return;
        }

        $freshdesk = new Freshdesk();
        $domains = Domain::get()->filter(['Enabled' => 1]);

        $this->log('Number of existing domains: '.$domains->count(), SS_Log::INFO);

        $alertDays = [
            Freshdesk::PRIORITY_LOW => $siteConfig->OpsGenieDaysUntilP5,
            Freshdesk::PRIORITY_MEDIUM => $siteConfig->OpsGenieDaysUntilP3,
            Freshdesk::PRIORITY_HIGH => $siteConfig->OpsGenieDaysUntilP2,
            Freshdesk::PRIORITY_URGENT => $siteConfig->OpsGenieDaysUntilP1,
        ];

        $startAlerting = max(array_values($alertDays));

        foreach ($domains as $d) {
            if (!$d->CurrentCertificate()->exists()) {
                continue;
            }

            $cert = $d->CurrentCertificate();

            $this->log('Checking '.$d->Domain, SS_Log::INFO);
            $this->log('Days until expiration: '.$cert->DaysUntilExpiration, SS_Log::DEBUG);

            // Skip if we're outside the alerting threshold
            if ($cert->DaysUntilExpiration > $startAlerting) {
                // If there is a current OpsGenie alert try and close it as the cert may have been updated
                if ($d->FreshdeskID) {
                    $this->log('Closing existing Freshdesk ticket '.$d->FreshdeskID, SS_Log::INFO);

                    $freshdesk->closeTicket($d->FreshdeskID, $this->createTicketBody($d, $cert, 'FreshdeskTicketClosed'));

                    $d->FreshdeskID = '';
                    $d->FreshdeskPriority = '';
                    $d->write();
                }

                continue;
            }

            $priority = $this->closestNumber($cert->DaysUntilExpiration, $alertDays);
            $this->log('Current alert priority: '.$priority, SS_Log::DEBUG);

            // Create an Freshdesk ticket if one isn't already made
            if (!$d->FreshdeskID) {
                $this->log('Creating Freshdesk ticket', SS_Log::INFO);

                $ticket = $freshdesk->createTicket([
                    'subject' => $d->Domain.' certificate expires '.$cert->ValidTo,
                    'description' => $this->createTicketBody($d, $cert),
                    'priority' => $priority,
                    'group_id' => (int) $siteConfig->FreshdeskGroupID,
                    'product_id' => (int) $siteConfig->FreshdeskProductID,
                    'requester_id' => (int) FRESHDESK_USER_ID,
                    'status' => Freshdesk::STATUS_OPEN,
                    'tags' => ['ssl', 'locksmith'],
                ]);

                $d->FreshdeskID = $ticket->id;
                $d->FreshdeskPriority = $priority;

                $d->write();
            } elseif ((int) $priority !== (int) $d->FreshdeskPriority) {
                // Upgrade the priority if its different
                $this->log('Upgrading Freshdesk ticket from '.$d->FreshdeskPriority.' to '.$priority, SS_Log::INFO);
                $freshdesk->addNote($d->FreshdeskID, 'Escalating Freshdesk ticket priority from '.Freshdesk::PriorityAsString($d->FreshdeskPriority).' to '.Freshdesk::PriorityAsString($priority));

                $freshdesk->updateTicket($d->FreshdeskID, [
                    'priority' => $priority,
                ]);

                $d->FreshdeskPriority = $priority;
                $d->write();
            }
        }
    }

    /**
     * Creates the body of the ticket with information about the domain.
     *
     * @param $domain
     * @param $cert
     * @param string $template
     *
     * @return HTMLText
     */
    private function createTicketBody($domain, $cert, $template = 'FreshdeskTicket')
    {
        $arrayData = new ArrayData([
            'Domain' => $domain,
            'Certificate' => $cert,
        ]);

        return $arrayData->renderWith($template)->RAW();
    }
}
