<?php namespace LambdaSolutions;

/**
* Export Salesforce CRM data to Zendesk customer ticketing system.
* This script may be set up as a cron job, scheduled to run every few minutes.
* See README.md for sample crontab.
*/

// Include dependencies.
include './Salesforce.php';
include './Zendesk.php';
include './SalesforceToZendesk.php';

// Run this task.
$task = new SalesforceToZendesk();

if($task->Run())
	echo "Success.";
else
	echo "Failure.";
