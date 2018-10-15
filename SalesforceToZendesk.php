<?php namespace LambdaSolutions;

/**
* Export Salesforce CRM data to Zendesk customer ticketing system.
*/
class SalesforceToZendesk
{
	// API libraries.
	public $salesforce = null;
	public $zendesk = null;

	// Credentials.
	public $salesforce_username = '';
	public $salesforce_password = '';
	public $salesforce_client_id = '';
	public $salesforce_client_secret = '';
	public $zendesk_username = '';
	public $zendesk_subdomain = '';
	public $zendesk_token = '';

	// Imported data to transfer.
	public $import_accounts = array();
	public $import_contacts = array();
	public $import_products = array();

	/**
	* Run this task.
	* @param string $salesforce_username Salesforce credentials.
	* @param string $salesforce_password Salesforce credentials.
	* @param string $salesforce_client_id Salesforce credentials.
	* @param string $salesforce_client_secret Salesforce credentials.
	* @param string $zendesk_username Zendesk credentials.
	* @param string $zendesk_subdomain Zendesk credentials.
	* @param string $zendesk_token Zendesk credentials.
	* @return bool|String True on success. False on failure.
	*/
	public function Run($salesforce_username = '', $salesforce_password = '', $salesforce_client_id = '', $salesforce_client_secret = '', $zendesk_username = '', $zendesk_subdomain = '', $zendesk_token = '')
	{
		// Check environment variables first, then fall back to passed-in credentials.
		$this->salesforce_username = getenv("SALESFORCE_USERNAME") ?: $salesforce_username;
		$this->salesforce_password = getenv("SALESFORCE_PASSWORD") ?: $salesforce_password;
		$this->salesforce_client_id = getenv("SALESFORCE_CLIENT_ID") ?: $salesforce_client_id;
		$this->salesforce_client_secret = getenv("SALESFORCE_CLIENT_SECRET") ?: $salesforce_client_secret;
		$this->zendesk_username = getenv("ZENDESK_USERNAME") ?: $zendesk_username;
		$this->zendesk_subdomain = getenv("ZENDESK_SUBDOMAIN") ?: $zendesk_subdomain;
		$this->zendesk_token = getenv("ZENDESK_TOKEN") ?: $zendesk_token;
		
		// Salesforce setup.
		$this->salesforce = new Salesforce(
			$this->salesforce_username,
			$this->salesforce_password,
			$this->salesforce_client_id,
			$this->salesforce_client_secret
		);

		// Zendesk setup.
		$this->zendesk = new Zendesk(
			$this->zendesk_username,
			$this->zendesk_subdomain,
			$this->zendesk_token
		);

		// Run all sub-tasks.
		if(!$this->salesforce->Authenticate())
			return false;
		if(!$this->SalesforcePull())
			return false;
		if(!$this->zendesk->Authenticate())
			return false;
		if(!$this->ZendeskPush())
			return false;

		return true; // Success.
	}

	/**
	* Pull importable data from Salesforce API.
	* @return bool True on success. False on failure.
	*/
	private function SalesforcePull()
	{
		// Defaults.
		$this->import_accounts = array();
		$this->import_contacts = array();
		$this->import_products = array();

		// Generate Salesforce-compatible time stamps.
		$date_start = urlencode(date(DATE_ISO8601, strtotime('-1 week')));
		$date_end = urlencode(date(DATE_ISO8601, strtotime('now')));

		// Retrieve recent Account updates.
		$output = $this->salesforce->QueryObject("Account/updated/?start=".$date_start."&end=".$date_end);
		
		// Any new changes?
		if(!isset($output['ids']))
			return false;

		// Scan changed Accounts for importable data.
		foreach($output['ids'] as $id)
		{
			// Defaults.
			$owner = 'None';
			$name = 'None';
			$website = 'None';
			$phone_number = 'None';
			$address = 'None';

			// Get Account manager name.
			$temp = $this->salesforce->QueryObject("Account/".$id."/Owner");
			
			if(isset($temp["Name"]))
				$owner = $temp["Name"];

			// Get Account details.
			$temp = $this->salesforce->QueryObject("Account/".$id);

			if(isset($temp["Name"]))
				$name = $temp["Name"];
			if(isset($temp["Website"]))
				$website = $temp["Website"];
			if(isset($temp["Phone"]))
				$phone_number = $temp["Phone"];
			if(isset($temp["BillingStreet"]))
				$address = $temp["BillingStreet"];
			if(isset($temp["BillingCity"]))
				$address .= ", ".$temp["BillingCity"];
			if(isset($temp["BillingState"]))
				$address .= ", ".$temp["BillingState"];
			if(isset($temp["BillingPostalCode"]))
				$address .= ", ".$temp["BillingPostalCode"];
			if(isset($temp["BillingCountry"]))
				$address .= ", ".$temp["BillingCountry"];

			// Build import data.
			$this->import_accounts[$id] = array(
				"account_owner" => $owner,
				"name" => $name,
				"website" => $website,
				"address" => $address,
				"phone_number" => $phone_number
				);

			// Get Account contacts.
			$temp = $this->salesforce->QueryObject("Account/".$id."/Contacts");

			if(isset($temp["records"]))
				$temp = $temp["records"];

			// Scan Contacts on this Account.
			foreach($temp as $contact)
			{
				// Defaults.
				$name = 'None';
				$email = 'None';

				if(isset($contact["Name"]))
					$name = $contact["Name"];
				if(isset($contact["Email"]))
					$email = $contact["Email"];

				// Build import data.
				$this->import_contacts[] = array(
					"name" => $name,
					"email" => $email,
					"external_id" => $id
					);
			}

			// Get Account Opportunities.
			$temp = $this->salesforce->QueryObject("Account/".$id."/Opportunities");
			
			if(isset($temp["records"]))
				$temp = $temp["records"];

			// Scan Opportunities on this Account for Products (Line Items).
			foreach($temp as $opportunity)
			{	
				if(!isset($opportunity["Id"]))
					continue;

				$products = $this->salesforce->QueryObject("Opportunity/".$opportunity["Id"]."/OpportunityLineItems");

				if(isset($products["records"]))
					$products = $products["records"];

				// Defaults.
				$package_type = '';

				// Scan Products (Line Items) on this Opportunity.
				foreach($products as $product)
				{	
					// Build import data.
					if(isset($product["Name"]))
						$package_type .= $product["Name"].PHP_EOL;
				}

				$this->import_products[$id] = $package_type;
			}
		}

		return true;
	}

	/**
	* Push data to Zendesk API.
	* @return bool True on success. False on failure.
	*/
	private function ZendeskPush()
	{
		// Create or Update organizations with data from Salesforce.
		foreach($this->import_accounts as $id => $accounts)
		{
			$organization = array();
			$organization_fields = array();

			// Use the Salesforce unique ID as the external ID.
			$organization["external_id"] = $id; 
			if(isset($this->import_accounts[$id]))
				$organization["name"] = $this->import_accounts[$id]["name"];

			// Only export data that we have available to update.
			if(isset($this->import_accounts[$id]))
				$organization_fields["account_owner"] = $this->import_accounts[$id]["account_owner"];
			if(isset($this->import_accounts[$id]))
				$organization_fields["website"] = $this->import_accounts[$id]["website"];
			if(isset($this->import_accounts[$id]))
				$organization_fields["address"] = $this->import_accounts[$id]["address"];
			if(isset($this->import_accounts[$id]))
				$organization_fields["phone_number"] = $this->import_accounts[$id]["phone_number"];
			if(isset($this->import_products[$id]))
				$organization_fields["package_type"] = $this->import_products[$id];

			// Add custom organization fields as a sub-array.
			$organization["organization_fields"] = $organization_fields;
			$organization = array('organization' => $organization);

			$this->zendesk->Query("organizations/create_or_update.json", "POST", $organization);
		}

		// Create or Update users with data from Salesforce.
		foreach($this->import_contacts as $contact)
		{
			// Link this user to their organization in Zendesk.
			// Get associated Zendesk organization ID using external Salesforce ID.
			$output = $this->zendesk->Query("organizations/search.json?external_id=".$contact['external_id']);

			if(isset($output['organizations'][0]['id']))
				$contact['organization_id'] = $output['organizations'][0]['id'];
			$contact = array('user' => $contact);

			// Create/update this User in Zendesk.
			$output = $this->zendesk->Query("users/create_or_update.json", "POST", $contact);
		}

		return true;
	}
}
