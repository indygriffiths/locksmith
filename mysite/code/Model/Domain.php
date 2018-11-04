<?php

class Domain extends DataObject
{
    private static $db = [
        'Domain' => 'Varchar(255)',
        'Source' => 'Enum("Manual,CloudFlare,Incapsula")',
        'SourceID' => 'Text',
        'Enabled' => 'Boolean(1)',
        'LastChecked' => 'SS_DateTime',
        'ErrorCode' => 'Text',
        'ErrorMessage' => 'Text',
        'OpsGenieID' => 'Text',
        'AlertPriority' => 'Text',
        'AlertedCommonNameMismatch' => 'Boolean(0)',
        'HasBeenChecked' => 'Boolean(0)',
        'FreshdeskID' => 'Text',
        'FreshdeskPriority' => 'Text',
        'StackID' => 'Text',
    ];

    private static $has_many = [
        'Certificates' => 'Certificate',
    ];

    private static $summary_fields = [
        'Domain' => 'Domain',
        'Source' => 'Source',
        'LastCheckSuccessfulNice' => 'Last Check Successful?',
        'HasValidCertificateNice' => 'Has a Valid Certificate?',
        'CurrentCertificate.Name' => 'Certificate Name',
        'CurrentCertificate.Issuer' => 'Issuer',
        'CurrentCertificate.ValidFrom' => 'Valid From',
        'CurrentCertificate.ValidTo' => 'Valid Until',
        'Enabled.Nice' => 'Enabled',
    ];

    private static $defaults = [
        'Enabled' => true,
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('Domain')
            ->setDescription('The domain to check, without the protocol');

        $fields->dataFieldByName('Enabled')
            ->setDescription('Untick to prevent future checks for this domain');

        $fields->dataFieldByName('ErrorCode')
            ->setRows(1)
            ->setDescription("The error code returned by stream_socket_client, or 999 if the certificate couldn't be read");

        $fields->dataFieldByName('SourceID')
            ->setRows(1);

        $fields->dataFieldByName('ErrorMessage')
            ->setDescription('The error message returned by stream_socket_client or openssl_error_string');

        $fields->dataFieldByName('OpsGenieID')
            ->setRows(1)
            ->setDescription('The ID of the OpsGenie alert for this domain. Set to empty if there is no alert');

        $fields->dataFieldByName('FreshdeskID')
            ->setRows(1)
            ->setDescription('The ID of the Freshdesk ticket for this domain. Set to empty if there is no alert');

        $fields->dataFieldByName('AlertPriority')
            ->setRows(1)
            ->setReadonly(true)
            ->setDescription('The current status of the OpsGenie alert (P5 to P1)');

        $fields->dataFieldByName('FreshdeskPriority')
            ->setRows(1)
            ->setReadonly(true)
            ->setDescription('The current priority of the Freshdesk ticket (1 to 4)');

        $fields->addFieldToTab('Root.Certificates', GridField::create(
            'Certificates',
            'Certificates recorded for this domain',
            $this->Certificates(),
            $config = GridFieldConfig_RecordEditor::create()
        ));

        $config->removeComponentsByType(new GridFieldAddNewButton());
        $config->addComponent(new GridFieldExportButton());

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Strip out the protocol
        if (false === strpos($this->Domain, '//')) {
            $this->Domain = 'http://'.$this->Domain;
        }

        $this->Domain = parse_url($this->Domain, PHP_URL_HOST);
    }

    /**
     * @return RequiredFields
     */
    public function getCMSValidator()
    {
        return new RequiredFields('Domain');
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->Domain;
    }

    /**
     * Returns the current cert for the site.
     *
     * @return Certificate
     */
    public function CurrentCertificate()
    {
        $cert = $this->Certificates()->sort('ID DESC')->limit(1);
        if (!$cert || !$cert->first()) {
            return Certificate::create();
        }

        return $cert->first();
    }

    /**
     * @return bool True if the certificate has expired
     */
    public function HasValidCertificate()
    {
        return $this->CurrentCertificate()->IsValid;
    }

    /**
     * @return string Yes if the certificate has expired
     */
    public function HasValidCertificateNice()
    {
        return $this->HasValidCertificate() ? 'Yes' : 'No';
    }

    /**
     * @return bool If the last check we performed was successful
     */
    public function LastCheckSuccessful()
    {
        return empty($this->ErrorCode) &&
            empty($this->ErrorMessage) &&
            !empty($this->LastChecked) &&
            $this->HasValidCertificate();
    }

    /**
     * @return string If the last check we performed was successful
     */
    public function LastCheckSuccessfulNice()
    {
        return $this->LastCheckSuccessful() ? 'Yes' : 'No';
    }
}
