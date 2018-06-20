<?php

/**
 * Goes through all domains and checks their SSL certificates
 */
class CheckSSLCertificates implements CronTask {
    /**
     * @return string
     */
    public function getSchedule() {
        return "0/30 * * * *";
    }

    /**
     * @throws ValidationException
     */
    public function process() {
        // Get all enabled domains
        $domains = Domain::get()->filter(['Enabled' => 1]);

        $this->log('Number of existing domains: '.$domains->count(), SS_Log::DEBUG);

        foreach ($domains as $d) {
            $d->LastChecked = time();
            $d->HasCertificate = false;

            // Get the socket stream and check if it failed
            $stream = $this->getStream($d->Domain);
            if (isset($stream['error'])) {
                $d->ErrorCode = $stream['code'];
                $d->ErrorMessage = $stream['message'];

                $this->log('Couldn\'t fetch URL: '.$d->ErrorMessage, SS_Log::INFO);

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

            $d->HasCertificate = true;
            $d->ErrorCode = "";
            $d->ErrorMessage = "";

            // Get the latest cert we have on file for this domain
            $lastCert = $d->CurrentCertificate();

            $this->log('Checking this cert against the current one', SS_Log::DEBUG);
            if ($lastCert->exists() && $lastCert->Fingerprint === $fingerprint) {
                // Still has the same cert, don't bother adding it again
                $d->write();

                $this->log('Cert for '.$d->ID.' hasn\'t changed, not adding', SS_Log::DEBUG);
                continue;
            }

            // Create the new cert
            $newCert = Certificate::create();
            $newCert->DomainID = $d->ID;
            $newCert->Name = $cert['name'];
            $newCert->Issuer = $cert['issuer']['CN'];
            $newCert->Type = $cert['signatureTypeSN'];
            $newCert->Domains = $cert['extensions']['subjectAltName'];
            $newCert->ValidFrom = date('Y-m-d H:i:s', $cert['validFrom_time_t']);
            $newCert->ValidTo = date('Y-m-d H:i:s', $cert['validTo_time_t']);
            $newCert->Serial = $cert['serialNumberHex'];
            $newCert->Fingerprint = $fingerprint;
            $newCert->write();

            $d->write();

            $this->log('Adding new certificate for '.$d->ID, SS_Log::INFO);
        }
    }

    /**
     * Opens a socket connection with the server
     * @param string $domain
     * @return array
     */
    private function getStream($domain) {
        $get = stream_context_create([
            "ssl" => [
                "capture_peer_cert" => true,
                "verify_peer"       => true,
                "verify_peer_name"  => true
            ]
        ]);

        $read = stream_socket_client("ssl://".$domain.":443", $errno, $errstr, $this->config()->socket_timeout, STREAM_CLIENT_CONNECT, $get);

        if(!$read) {
            return [
                'error'   => true,
                'code'    => $errno,
                'message' => empty($errstr) ? 'Unspecified error opening SSL socket' : $errstr
            ];
        }

        return stream_context_get_params($read);
    }

    /**
     * @param $message
     * @param int $level
     */
    private function log($message, $level = SS_Log::INFO) {
        SS_Log::log($message, $level);
        if(Director::is_cli()) {
            echo $message.PHP_EOL;
        } else {
            echo $message."<br>".PHP_EOL;
        }
    }
}
