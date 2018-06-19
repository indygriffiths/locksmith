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
            $isSelfSigned = new CheckboxField('IsSelfSigned', 'Is Self Signed?'),
            $usesSha1 = new CheckboxField('UsesSha1Hash', 'Uses SHA-1 Hash?'),
        ]);

        $fields->dataFieldByName('Type')->setRows(1);
        $fields->dataFieldByName('Issuer')->setRows(1);

        $isValid->setDisabled(true);
        $isSelfSigned->setDisabled(true);
        $usesSha1->setDisabled(true);

        return $fields;
    }

    /**
     * @return bool True if the certificate has expired
     */
    public function getIsValid() {
        return (
            date('U', strtotime($this->ValidFrom)) < time() &&
            date('U', strtotime($this->ValidTo)) >= time() &&
            !$this->IsSelfSigned &&
            !$this->UsesSha1Hash
        );
    }

    /**
     * @return bool True if the issuer is the current domain
     */
    public function getIsSelfSigned() {
        return $this->Issuer === $this->Domain()->Domain;
    }

    /**
     * @return bool True if the certificate uses an older SHA1 hash
     */
    public function getUsesSha1Hash() {
        return $this->Type === 'RSA-SHA1';
    }
}