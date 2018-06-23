<?php

/**
 * Class for managing Slack settings
 */
class SlackSiteConfigExtension extends DataExtension {
    private static $db = [
        'SlackChannel' => 'Varchar(100)',
        'SlackEmoji' => 'Varchar(100)',
        'SlackPostOnCertificateUpdate' => 'Boolean(1)',
        'SlackPostOnCommonNameMismatch' => 'Boolean(1)',
        'SlackPostOnCheckFailure' => 'Boolean(1)',
    ];

    private static $defaults = [
        'SlackEmoji' => ':lock:'
    ];

    public function updateCMSFields(FieldList $fields) {
        if(!defined('SLACK_WEBHOOK_URL')) {
            $fields->addFieldToTab('Root.Slack', new LiteralField("MissingSlackWebhook", "<p class=\"message warning\">SLACK_WEBHOOK_URL not defined, integration is disabled</p>"));
        }

        $fields->addFieldsToTab('Root.Slack', [
            new TextField('SlackChannel', 'Slack Channel'),
            new TextField('SlackEmoji', 'Slack Emoji'),
            new CheckboxField('SlackPostOnCertificateUpdate', 'Post to Slack when a certificate is updated'),
            new CheckboxField('SlackPostOnCheckFailure', 'Post to Slack when a certificate check has failed after being successful'),
            new CheckboxField('SlackPostOnCommonNameMismatch', 'Post to Slack when a certificate has a common name mismatch'),
        ]);
    }
}