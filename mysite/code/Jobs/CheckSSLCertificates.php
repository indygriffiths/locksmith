<?php

/**
 * Goes through all domains and checks their SSL certificates.
 */
class CheckSSLCertificates implements CronTask
{
    use CronTaskUtilities;

    /**
     * @return string
     */
    public function getSchedule()
    {
        return '0/30 * * * *';
    }

    /**
     * @throws ValidationException
     */
    public function process()
    {
        // Get all enabled domains
        $domains = Domain::get()->filter(['Enabled' => 1]);

        $this->log('Number of existing domains: '.$domains->count(), SS_Log::DEBUG);

        foreach ($domains as $d) {
            $hasBeenChecked = $d->HasBeenChecked;
            $d->LastChecked = time();

            $this->log('Checking '.$d->Domain, SS_Log::INFO);

            if ($d->LastCheckSuccessful()) {
                $this->log('Last check for '.$d->Domain.' was successful', SS_Log::INFO);
            } else {
                $this->log('Last check for '.$d->Domain.' was not successful', SS_Log::INFO);
            }

            // Get the latest cert we have on file for this domain
            $lastCert = $d->CurrentCertificate();
            $hasValidCert = ($lastCert->exists() && $lastCert->IsValid);
            if ($hasValidCert) {
                $this->log($d->Domain.' has an existing valid certificate', SS_Log::INFO);
            }

            // Get the socket stream and check if it failed
            $stream = $this->getStream($d->Domain);
            if (isset($stream['error'])) {
                $this->log('Couldn\'t fetch for domain '.$d->Domain.': '.$stream['message'], SS_Log::INFO);

                if ($d->LastCheckSuccessful()) {
                    if (!$d->AlertedCommonNameMismatch && false !== stripos($stream['message'], 'did not match expected CN')) {
                        // Notify Ops about the mismatch, but only once
                        $this->notifyCommonNameMismatch($d, $stream['message']);
                        $d->AlertedCommonNameMismatch = true;
                    } else {
                        // Notify on the general failure
                        $this->notifyFailure($d, $stream['message']);
                    }
                }

                $d->ErrorCode = $stream['code'];
                $d->ErrorMessage = $stream['message'];

                $d->write();

                continue;
            }

            $this->log('Attempting to fetch certificate', SS_Log::DEBUG);

            // Attempt to parse the certificate
            $cert = openssl_x509_parse($stream['options']['ssl']['peer_certificate']);
            $fingerprint = openssl_x509_fingerprint($stream['options']['ssl']['peer_certificate']);

            if (!$cert || !isset($stream['options']['ssl']['peer_certificate'])) {
                $d->ErrorCode = 999;
                // Bit of a hack, as openssl_error_string can return multiple error
                // strings and you have to loop over to get the "latest" one
                while ($msg = openssl_error_string()) {
                    $d->ErrorMessage = $msg;
                }

                $this->log('Couldn\'t decode cert: '.$d->ErrorMessage, SS_Log::WARN);
                $d->write();

                continue;
            }

            $d->ErrorCode = '';
            $d->ErrorMessage = '';
            $d->AlertedCommonNameMismatch = false;
            $d->HasBeenChecked = true;

            $this->log('Checking this certificate against the current one', SS_Log::DEBUG);
            if ($lastCert->exists() && $lastCert->Fingerprint === $fingerprint) {
                // Still has the same cert, don't bother adding it again
                $d->write();

                $this->log('Certificate for '.$d->Domain.' hasn\'t changed, not adding', SS_Log::DEBUG);

                continue;
            }

            $this->log('Adding new certificate', SS_Log::DEBUG);

            // Create the new cert
            $newCert = Certificate::create();
            $newCert->DomainID = $d->ID;
            $newCert->Name = $cert['name'];
            $newCert->Issuer = $cert['issuer']['CN'];
            $newCert->Type = $cert['signatureTypeSN'];
            $newCert->Domains = $cert['extensions']['subjectAltName'];
            $newCert->ValidFrom = date('Y-m-d H:i:s', $cert['validFrom_time_t']);
            $newCert->ValidTo = date('Y-m-d H:i:s', $cert['validTo_time_t']);
            $newCert->Serial = $cert['serialNumber'];
            $newCert->Fingerprint = $fingerprint;
            $newCert->write();

            $this->log('New certificate is ID '.$newCert->ID.' with fingerprint '.$newCert->Fingerprint, SS_Log::DEBUG);

            $d->write();

            // Post to Slack about the new certificate only if this isn't the first certificate
            if ($hasBeenChecked) {
                $this->notifyNewCertificate($d, $cert);
            }
        }
    }

    /**
     * Posts a message to Slack about the new certificate detected.
     *
     * @param Domain $site The website for this cert
     * @param array  $cert New cert that's been picked up
     */
    protected function notifyNewCertificate($site, $cert)
    {
        if (!defined('SLACK_WEBHOOK_URL') || !SiteConfig::current_site_config()->SlackPostOnCertificateUpdate) {
            $this->log('SLACK_WEBHOOK_URL not defined or Slack updates disabled, not sending', SS_Log::DEBUG);

            return;
        }

        $this->log('Sending notification to Slack for the new certificate', SS_Log::DEBUG);

        $settings = [
            'username' => SiteConfig::current_site_config()->Title,
            'channel' => SiteConfig::current_site_config()->SlackChannel,
            'icon' => SiteConfig::current_site_config()->SlackEmoji
        ];

        $client = new Maknz\Slack\Client(SLACK_WEBHOOK_URL, $settings);
        $client->attach([
            'color' => 'warning',
            'fields' => [
                [
                    'title' => 'Name',
                    'value' => $cert['name'],
                ],
                [
                    'title' => 'Domains',
                    'value' => $cert['extensions']['subjectAltName'],
                ],
                [
                    'title' => 'Issuer',
                    'value' => $cert['issuer']['CN'],
                ],
                [
                    'title' => 'Valid From',
                    'value' => date('Y-m-d H:i:s', $cert['validFrom_time_t']),
                    'short' => true,
                ],
                [
                    'title' => 'Valid To',
                    'value' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
                    'short' => true,
                ],
            ],
        ])->send('A new certificate has been detected for '.$site->Domain);
    }

    /**
     * Posts a message to Slack when a cert that was successful is now failed.
     *
     * @param Domain $site  The website for this cert
     * @param string $error Error returned by OpenSSL
     */
    protected function notifyFailure($site, $error)
    {
        if (!defined('SLACK_WEBHOOK_URL') || !SiteConfig::current_site_config()->SlackPostOnCheckFailure) {
            $this->log('SLACK_WEBHOOK_URL not defined or Slack updates disabled, not sending', SS_Log::DEBUG);

            return;
        }

        $this->log('Sending notification to Slack as the check is now failing', SS_Log::DEBUG);

        $settings = [
            'username' => SiteConfig::current_site_config()->Title,
            'channel' => SiteConfig::current_site_config()->SlackChannel,
            'icon' => SiteConfig::current_site_config()->SlackEmoji
        ];

        $client = new Maknz\Slack\Client(SLACK_WEBHOOK_URL, $settings);
        $client->attach([
            'color' => 'danger',
            'title' => $error,
        ])->send('The certificate check for '.$site->Domain.' is failing. HTTPS requests to this domain may be failing for end-users.');
    }

    /**
     * Posts a message to Slack when the cert is mismatched.
     *
     * @param Domain $site  The website for this cert
     * @param string $error Error returned by OpenSSL
     */
    protected function notifyCommonNameMismatch($site, $error)
    {
        if (!defined('SLACK_WEBHOOK_URL') || !SiteConfig::current_site_config()->SlackPostOnCommonNameMismatch) {
            $this->log('SLACK_WEBHOOK_URL not defined or Slack updates disabled, not sending', SS_Log::DEBUG);

            return;
        }

        $this->log('Sending notification to Slack for a common name mismatch', SS_Log::DEBUG);

        $settings = [
            'username' => SiteConfig::current_site_config()->Title,
            'channel' => SiteConfig::current_site_config()->SlackChannel,
            'icon' => SiteConfig::current_site_config()->SlackEmoji
        ];

        $client = new Maknz\Slack\Client(SLACK_WEBHOOK_URL, $settings);
        $client->attach([
            'color' => 'danger',
            'title' => $error,
        ])->send('A common name mismatch has been detected for domain '.$site->Domain.'. HTTPS requests to this domain may be failing for end-users.');
    }

    /**
     * Opens a socket connection with the server.
     *
     * @param string $domain
     *
     * @return array
     */
    private function getStream($domain)
    {
        $streamOptions = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
            ],
        ]);

        /**
         * In true PHP stupidity, stream_socket_client emits warnings when a connection can't be made, and will
         * emit multiple warnings but only return the final warning for its $errno and $errstr parameters, meaning
         * that you have to set a temporary error handler to capture these warnings so you know why the socket failed, as
         * the final warning is usually "Unknown error".
         *
         * What the fuck.
         */
        $socketErrors = [];
        set_error_handler(function ($errno, $errstr) use (&$socketErrors) {
            $socketErrors[] = str_replace('stream_socket_client(): ', '', $errstr);
        }, E_WARNING);

        $read = stream_socket_client('ssl://'.$domain.':443', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $streamOptions);
        restore_error_handler();

        if (!empty($socketErrors)) {
            return [
                'error' => true,
                'code' => $errno,
                'message' => $socketErrors[0],
            ];
        }

        return stream_context_get_params($read);
    }
}
