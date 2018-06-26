<?php

/**
 * Class for managing OpsGenie settings around alerting for expiring certificates.
 */
class OpsGenieSiteConfigExtension extends DataExtension
{
    private static $db = [
        'CreateOpsGenieAlert' => 'Boolean(0)',
        'OpsGenieDaysUntilP5' => 'Int',
        'OpsGenieDaysUntilP4' => 'Int',
        'OpsGenieDaysUntilP3' => 'Int',
        'OpsGenieDaysUntilP2' => 'Int',
        'OpsGenieDaysUntilP1' => 'Int',
    ];

    private static $defaults = [
        'OpsGenieDaysUntilP5' => 30,
        'OpsGenieDaysUntilP4' => 14,
        'OpsGenieDaysUntilP3' => 7,
        'OpsGenieDaysUntilP2' => 3,
        'OpsGenieDaysUntilP1' => 0,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!defined('OPSGENIE_API_KEY')) {
            $fields->addFieldToTab('Root.OpsGenie', new LiteralField('MissingOpsGenieKey', '<p class="message warning">OPSGENIE_API_KEY not defined, integration is disabled</p>'));
        }

        $fields->addFieldsToTab('Root.OpsGenie', [
            new CheckboxField('CreateOpsGenieAlert', 'Create OpsGenie Alerts for certificates about to expire'),
            new HeaderField('Alert Days Until Expiration'),
            new NumericField('OpsGenieDaysUntilP5', 'Create P5 Alert (Informational)'),
            new NumericField('OpsGenieDaysUntilP4', 'Escalate to P4 (Low)'),
            new NumericField('OpsGenieDaysUntilP3', 'Escalate to P3 (Moderate)'),
            new NumericField('OpsGenieDaysUntilP2', 'Escalate to P2 (High)'),
            new NumericField('OpsGenieDaysUntilP1', 'Escalate to P1 (Critical)'),
        ]);
    }
}
