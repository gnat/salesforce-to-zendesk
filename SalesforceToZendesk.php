<?php

/**
* Salesforce to Zendesk task.
* 
* This script should be set up as a cron job and scheduled to run every few minutes.
*/
class SalesforceToZendesk
{
	/**
	* Run this task.
	* @return bool|String True on success. False on failure.
	*/
	public function Run()
	{
		// Run all sub-tasks.
		if(!$this->SalesforceAuth())
			return false;
		if(!$this->SalesforcePull())
			return false;
		if(!$this->ZendeskAuth())
			return false;
		if(!$this->ZendeskPush())
			return false;

		return true;
	}

	/**
	* Authenticate with Salesforce API.
	* @return bool True on success. False on failure.
	*/
	private function SalesforceAuth()
	{
		return false;
	}

	/**
	* Pull data from Salesforce API.
	* @return bool True on success. False on failure.
	*/
	private function SalesforcePull()
	{
		return false;
	}

	/**
	* Authenticate with Zendesk API.
	* @return bool True on success. False on failure.
	*/
	private function ZendeskAuth()
	{
		return false;
	}

	/**
	* Push data to Zendesk API.
	* @return bool True on success. False on failure.
	*/
	private function ZendeskPush()
	{
		return false;
	}
}

// Run this task.
$task = new SalesforceToZendesk();

if($task->Run())
	echo "Success.";
else
	echo "Failure.";
