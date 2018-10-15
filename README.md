### Salesforce to Zendesk integration.

Exports Salesforce CRM data to Zendesk customer ticketing system.

Written by Nathaniel Sabanski.

### Crontab sample.

```
*/10 * * * * cd /path/to/salesforce-to-zendesk; /usr/bin/php Run.php
```

### Environment Variables (Optional)

Environment variables can be used to pass in secrets for Docker support, and other container systems.

* SALESFORCE_USERNAME
* SALESFORCE_PASSWORD
* SALESFORCE_CLIENT_ID
* SALESFORCE_CLIENT_SECRET
* ZENDESK_USERNAME
* ZENDESK_SUBDOMAIN
* ZENDESK_TOKEN
