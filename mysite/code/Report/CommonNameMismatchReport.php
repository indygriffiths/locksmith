<?php

/**
 * Report for listing domains with a CN mismatch on their certificate.
 */
class CommonNameMismatchReport extends SS_Report
{
    public function title()
    {
        return 'Domains with common name mismatches on their certificate';
    }

    public function description()
    {
        return 'Lists domains where the certificate doesn\'t match the current domain';
    }

    public function sourceRecords($params = null)
    {
        return Domain::get()->filter(['ErrorMessage:PartialMatch' => 'did not match expected CN'])->sort('Domain');
    }

    public function columns()
    {
        return [
            'Domain' => 'Domain',
            'LastChecked.Nice' => 'Last Checked',
            'ErrorCode' => 'Error Code',
            'ErrorMessage' => 'Error Message',
        ];
    }
}
