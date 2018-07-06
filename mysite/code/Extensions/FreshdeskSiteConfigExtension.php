<?php

/**
 * Class for managing Freshdesk settings around alerting for expiring certificates.
 */
class FreshdeskSiteConfigExtension extends DataExtension
{
    private static $db = [
        'CreateFreshdeskTicket' => 'Boolean(0)',
        'FreshdeskGroupID' => 'Varchar(20)',
        'FreshdeskProductID' => 'Varchar(20)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!Freshdesk::IsAvailable()) {
            $fields->addFieldToTab('Root.Freshdesk', new LiteralField('MissingFreshdeskKey', '<p class="message warning">Freshdesk environment variables not defined, integration is disabled</p>'));
        }

        $fields->addFieldsToTab('Root.Freshdesk', [
            new CheckboxField('CreateFreshdeskTicket', 'Create Freshdesk tickets for certificates about to expire'),
            $groupId = new NumericField('FreshdeskGroupID', 'Freshdesk Group ID'),
            $productId = new NumericField('FreshdeskProductID', 'Freshdesk Product ID'),
        ]);

        $groupId->setDescription('Numeric ID of the group to triage the ticket to');
        $productId->setDescription('Numeric ID of the product this ticket belongs to');
    }
}
