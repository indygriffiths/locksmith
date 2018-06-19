<?php

class Domain extends DataObject {
    private static $db = [
        'Domain'         => 'Varchar(255)',
		'Source'         => 'Enum("Manual,CloudFlare,Incapsula")',
		'SourceID'       => 'Text',
		'Enabled'        => 'Boolean(1)',
        'HasCertificate' => 'Boolean(0)',
        'LastChecked'    => 'SS_DateTime',
        'ErrorCode'      => 'Text',
        'ErrorMessage'   => 'Text'
    ];

    private static $has_many = [
        "Certificates" => "Certificate",
    ];

    private static $summary_fields = [
        'Domain'                       => 'Domain',
        'HasValidCertificateNice'      => 'Has a Valid Certificate?',
        'CurrentCertificate.Name'      => 'Certificate Name',
        'CurrentCertificate.Issuer'    => 'Issuer',
        'CurrentCertificate.ValidFrom' => 'Valid From',
        'CurrentCertificate.ValidTo'   => 'Valid Until',
        'Enabled.Nice'                 => 'Enabled',
    ];

    private static $defaults = [
        'Enabled' => true
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('Domain')
               ->setDescription("The domain to check, without the protocol");

        $fields->dataFieldByName('Enabled')
               ->setDescription("Untick to prevent future checks for this domain");

        $fields->dataFieldByName('ErrorCode')
               ->setRows(1)
               ->setDescription("The error code returned by stream_socket_client, or 999 if the certificate couldn't be read");

        $fields->dataFieldByName('ErrorMessage')
               ->setRows(1)
               ->setDescription("The error message returned by stream_socket_client or openssl_error_string");

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

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Strip out the protocol
        if(strpos($this->Domain, "//") === false) {
            $this->Domain = "http://".$this->Domain;
        }

        $this->Domain = parse_url($this->Domain, PHP_URL_HOST);
    }

    /**
     * @return RequiredFields
     */
    public function getCMSValidator() {
        return new RequiredFields('Domain');
    }


    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->Domain;
    }

    /**
     * Returns the current cert for the site
     * @return Certificate
     */
    public function CurrentCertificate() {
        $cert = $this->Certificates()->sort('ID DESC')->limit(1);
        if(!$cert || !$cert->first()) {
            return Certificate::create();
        }

        return $cert->first();
    }

    /**
     * @return bool True if the certificate has expired
     */
    public function HasValidCertificate() {
        return $this->HasCertificate && $this->CurrentCertificate()->IsValid;
    }

    /**
     * @return string Yes if the certificate has expired
     */
    public function HasValidCertificateNice() {
        return $this->HasValidCertificate() ? "Yes" : "No";
    }
}
