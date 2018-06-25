<?php

/**
 * Class for representing a SSL certificate
 */
class Certificate extends DataObject {
    private static $db = [
        'Name'        => 'Text',
        'Type'        => 'Text',
        'Issuer'      => 'Text',
        'Domains'     => 'Text',
        'Serial'      => 'Varchar(100)',
        'Fingerprint' => 'Varchar(100)',
        'ValidFrom'   => 'SS_DateTime',
        'ValidTo'     => 'SS_DateTime',
    ];

    private static $has_one = [
        "Domain" => "Domain",
    ];

    private static $indexes = [
        'Serial' => true
    ];

    private static $summary_fields = [
        'ID'        => 'ID',
        'Name'      => 'Certificate Name',
        'Type'      => 'Type',
        'Issuer'    => 'Issuer',
        'Serial'    => 'Serial',
        'ValidFrom' => 'Valid From',
        'ValidTo'   => 'Valid Until',
    ];

    private static $default_sort = "ID DESC";

    /**
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            new ReadonlyField('Created', 'First Seen'),
            $isValid = new CheckboxField('IsValid', 'Certificate is Valid?'),
        ]);

        $fields->dataFieldByName('Type')->setRows(1);
        $fields->dataFieldByName('Issuer')->setRows(1);

        $isValid->setDisabled(true);

        return $fields;
    }

    /**
     * @return bool True if the certificate has expired
     */
    public function getIsValid() {
        return (
            date('U', strtotime($this->ValidFrom)) < time() &&
            date('U', strtotime($this->ValidTo)) >= time()
        );
    }

    /**
     * @return int Number of days until the certificate expires. Can return a negative number
     */
    public function getDaysUntilExpiration() {
        // Ignore times, they just end in more confusion
        $earlier = new DateTime(date('Y-m-d'));
        $later = new DateTime(date('Y-m-d', strtotime($this->ValidTo)));

        // We use %r%a to ensure we provide a - if the number of days is a negative
        return $earlier->diff($later)->format("%r%a");
    }
}