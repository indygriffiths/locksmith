<?php

/**
 * Report for listing domains where the certificate check is failing.
 */
class FailingDomainsReport extends SS_Report
{
    public function title()
    {
        return 'Domains failing SSL checks';
    }

    public function description()
    {
        return 'Lists domains where we couldn\'t get the current certificate status';
    }

    public function sourceRecords($params = null)
    {
        return Domain::get()->filter(['ErrorMessage:not' => ''])->sort('Domain');
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
