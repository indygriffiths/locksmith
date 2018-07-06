# Locksmith

Maintainer: Indy Griffiths (indy@silverstripe.com)

Locksmith is a utility for monitoring domains and their SSL certificates, partially inspired when a site had its certificate expire and no one noticed.

Out of the box, Locksmith will:

- Automatically import a list of domains from CloudFlare and/or Incapsula
- Post updates to Slack, such as:
  - Upcoming certificate expirations once a day
  - New certificates detected
  - Any new errors when checking a domain, such as a Common Name Mismatch
- Create OpsGenie alerts for expiring certificates, escalating the priority as the expiration date approaches

Using [colymba/silverstripe-restfulapi](https://github.com/colymba/silverstripe-restfulapi) a basic API is also bundled into Locksmith, allowing programmatic access to tracked domains and certificates.


## Configuration

### Incapsula Domain Import

When defined, every hour a request to Incapsula will be made to get all sites under the account for the API key. Currently this does not include subaccounts, which will need to be manually added for the time being.

```
INCAPSULA_API_ID=12345
INCAPSULA_API_KEY=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

### CloudFlare Domain Import

When defined, every hour a request to CloudFlate will be made to get all zones (sites) under the account for the API key.

```
CLOUDFLARE_API_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
CLOUDFLARE_USER_EMAIL=foo@silverstripe.com
```


### Freshdesk Alerting

When defined, upcoming certificate renewals will be created as Freshdesk tickets. As the certificate approaches expiration, the priority will be increased. If a new certificate is detected, the ticket will be closed.

```
FRESHDESK_API_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
FRESHDESK_USER_ID=123456789
FRESHDESK_DOMAIN=https://foo.freshdesk.com
```

Additional options can be configured in the CMS, such as the group and product the ticket should be created as.

### OpsGenie Alerting

When defined, any upcoming certificate renewals will be created as alerts in OpsGenie if configured in the Settings section of the CMS.

```
OPSGENIE_API_KEY=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

In the CMS, you can also define the days to start alerting, from P5 (defaults to 30 days), up to P1 (defaults to expiring today). These values are also used for the daily Slack alert.

### Slack Notifications

Slack notifications will send messages to a specific channel with updates and reminders for tracked domains and certificates. These settings, such as the channel to post in and which alerts to send, can be configured in the CMS in the Settings section.

```
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXXXXXXXX/XXXXXXXXX/XXXXXXXXXXXXXXXXXXXXXXXX
```

## Getting Started

Once you've set up your environment variables:

1. Check the OpsGenie settings in the CMS:
   - Disabled
   - Alerting days are correct
     - P5: 30
     - P4: 14
     - P3: 7
     - P2: 5
     - P1: 0
2. Check the Slack settings in the CMS:
   - Channel is set, including hash (e.g #ssl)
   - Emoji is set, including colons (e.g :lock:)
   - Alerts are disabled
    
2. Run either `dev/tasks/RunGetCloudFlareDomains` or `dev/tasks/RunGetIncapsulaDomains` to import your first domains. If not using the automatic importers, simply add some domains into the CMS admin under Managed Domains
3. Run `dev/tasks/RunCheckCertificates` to perform the initial check of your domains
4. Enable your OpsGenie and/or Slack alerts

