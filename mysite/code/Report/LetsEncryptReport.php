<?php

/**
 * Report for listing domains who are using Let's Encrypt
 */
class LetsEncryptReport extends SS_Report
{
    public function title()
    {
        return 'Domains using Let\'s Encrypt';
    }

    public function description()
    {
        return 'Lists domains that are using Let\'s Encrypt for their certificates';
    }

    public function sourceRecords($params = null)
    {
        return Domain::get()
                     ->filter(['Certificates.Issuer:PartialMatch' => 'Let\'s Encrypt'])
                     ->sort('Domain');
    }

    public function columns()
    {
        return [
            'Domain' => 'Domain',
            'CurrentCertificate.Name' => 'Certificate Name',
            'CurrentCertificate.Issuer' => 'Issuer',
            'CurrentCertificate.ValidFrom' => 'Valid From',
            'CurrentCertificate.ValidTo' => 'Valid Until',
            'ErrorCode' => 'Error Code',
            'ErrorMessage' => 'Error Message',
        ];
    }
}
