<?php

use Maknz\Slack\Attachment;

/**
 * Class for posting to Slack the certificates expiring soon.
 */
class DailyExpirationReminder implements CronTask
{
    use CronTaskUtilities;

    /**
     * @return string Cron expression
     */
    public function getSchedule()
    {
        return '0 10 * * *';
    }

    public function process()
    {
        $siteConfig = SiteConfig::current_site_config();
        if (!defined('SLACK_WEBHOOK_URL') || !$siteConfig->SlackPostDailyUpdate) {
            $this->log('Slack not enabled, task not running', SS_Log::INFO);

            return;
        }

        $domains = Domain::get()->filter(['Enabled' => 1]);
        $this->log('Number of existing domains: '.$domains->count(), SS_Log::INFO);

        $alertDays = [
            'P5' => $siteConfig->OpsGenieDaysUntilP5,
            'P4' => $siteConfig->OpsGenieDaysUntilP4,
            'P3' => $siteConfig->OpsGenieDaysUntilP3,
            'P2' => $siteConfig->OpsGenieDaysUntilP2,
            'P1' => $siteConfig->OpsGenieDaysUntilP1,
        ];

        $startAlerting = max(array_values($alertDays));
        $alerts = [];

        foreach ($domains as $d) {
            if (!$d->CurrentCertificate()->exists()) {
                $this->log('Skipping '.$d->Domain.' as it doesn\'t have a certificate', SS_Log::INFO);

                continue;
            }

            $cert = $d->CurrentCertificate();

            $this->log('Checking '.$d->Domain, SS_Log::INFO);
            $this->log('Days until expiration: '.$cert->DaysUntilExpiration, SS_Log::DEBUG);

            // Skip if we're outside the alerting threshold
            if ($cert->DaysUntilExpiration > $startAlerting) {
                continue;
            }

            $priority = $this->closestNumber($cert->DaysUntilExpiration, $alertDays);
            $this->log('Current alert priority: '.$priority, SS_Log::DEBUG);

            // Create the line for this domain with a link to open it in the tool
            $line = sprintf(
                '%s [<%s/admin/domains/Domain/EditForm/field/Domain/item/%s/edit|%s>]',
                $d->Domain,
                Director::absoluteBaseURL(),
                $d->ID,
                $cert->ValidTo
            );

            if (Freshdesk::IsAvailable() && $d->FreshdeskID) {
                $line .= sprintf(
                    ' [<%s/helpdesk/tickets/%s|#%s>]',
                    FRESHDESK_DOMAIN,
                    $d->FreshdeskID,
                    $d->FreshdeskID
                );
            }

            if ($cert->IsLetsEncrypt) {
                $line .= ' [Let\'s Encrypt]';
            }

            $alerts[$priority][] = $line;
        }

        if (empty($alerts)) {
            $this->log('No domains to alert to, bailing', SS_Log::INFO);

            return;
        }

        $this->log('Sending the daily Slack certificate expiration reminder', SS_Log::INFO);

        // Sort the array by the priority
        ksort($alerts);

        $settings = [
            'username' => SiteConfig::current_site_config()->Title,
            'channel' => SiteConfig::current_site_config()->SlackChannel,
            'icon' => SiteConfig::current_site_config()->SlackEmoji,
        ];

        $client = new Maknz\Slack\Client(SLACK_WEBHOOK_URL, $settings);
        $attachments = [];

        foreach ($alerts as $priority => $lines) {
            $attachments[] = new Attachment([
                'color' => $this->alertColor($priority),
                'author_name' => sprintf('%s (%d days or less)', $priority, $alertDays[$priority]),
                'text' => implode("\n", $lines),
                'mrkdwn_in' => ['text'],
            ]);
        }

        $client->setAttachments($attachments)->send('Certificates expiring soon are listed below. This alert is sent every day at 10am by <https://platform.silverstripe.com/naut/project/locksmit|Locksmith>.');
    }

    /**
     * Returns the color for the alert priority when posting to Slack.
     *
     * @param $priority
     *
     * @return string Hex color code
     */
    protected function alertColor($priority)
    {
        switch ($priority) {
            case 'P5':
                return '#1b8220';
            case 'P4':
                return '#71ce11';
            case 'P3':
                return '#f3c717';
            case 'P2':
                return '#FC8C45';
            case 'P1':
                return '#EF0909';
            default:
                return '#1b8220';
        }
    }
}
