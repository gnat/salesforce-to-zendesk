### Salesforce to Zendesk integration sample.

Written by Nathaniel Sabanski.

### Crontab sample.

```
*/10 * * * * cd /path/to/script; /usr/bin/flock -n /tmp/SalesforceToZendesk.lockfile -c "/usr/bin/php SalesforceToZendesk.php"
```
