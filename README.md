# Salesforce to Zendesk customer integration service.

Updates Zendesk customer ticketing system with customer data from Salesforce CRM.

Written by Nathaniel Sabanski.

## Crontab sample.

```
*/10 * * * * cd /path/to/salesforce-to-zendesk; /usr/bin/php Run.php
```

## Environment Variables (Optional)

Used to pass in secrets for usage with Docker and other container systems to run as microservice.

* SALESFORCE_USERNAME
* SALESFORCE_PASSWORD
* SALESFORCE_CLIENT_ID
* SALESFORCE_CLIENT_SECRET
* ZENDESK_USERNAME
* ZENDESK_SUBDOMAIN
* ZENDESK_TOKEN
