<?php namespace LambdaSolutions;

/**
* Salesforce API wrapper class. 
* Easily interact with the Salesforce website.
* Expand on this class with new features as desired.
*
* You can find the Salesforce REST API reference here:
* http://www.salesforce.com/us/developer/docs/api_rest/index.htm
*/
class Salesforce
{	
	// API Endpoints.
	public $url_login = 'https://login.salesforce.com/services/oauth2/token';

	// Post-Authentication information.
	public $access_token = '';
	public $instance_url = '';
	public $authenticated = false;

	// Credentials.
	public $username, $password, $client_id, $client_secret;

	/**
	* Constructor. You may pass in credentials here.
	* @param string $username User.
	* @param string $password User password.
	* @param string $client_id App ID.
	* @param string $client_secret App Secret.
	*/
	public function __construct($username, $password, $client_id, $client_secret)
	{
		$this->username = $username;
		$this->password = $password;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}

	/**
	* Authenticate with Salesforce API.
	* @return bool True on success. False on failure.
	*/
	public function Authenticate()
	{
		// Sanity check.
		if(!isset($this->username) || !isset($this->password) || !isset($this->client_id) || !isset($this->client_secret))
			return false;

		// Attempt to authenticate.
		$fields = 
			'grant_type=password'.
			'&client_id='.$this->client_id.
			'&client_secret='.$this->client_secret.
			'&username='.urlencode($this->username).
			'&password='.urlencode($this->password);

		// Query REST API.
		$curl = curl_init($this->url_login);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		$output = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		// Status 200 is a successful authentication.
		if($status == 200)
		{
			$output = json_decode($output, true);
			$this->access_token = $output['access_token'];
			$this->instance_url = $output['instance_url'];
			$this->authenticated = true;

			return true; // Success.
		}

		echo 'Error: Failed to authenticate with Salesforce. ('.__FILE__.', Line '.__LINE__.')';

		return false; // Fail.
	}

	/**
	* Query Salesforce API.
	* @param string $url URL endpoint for API to query.
	* @param array $post POST data to send with API query.
	* @return array Returned data on success. Null on failure.
	*/
	public function Query($url, $post = '')
	{
		// Not authenticated.
		if(!$this->authenticated)
			return null;

		// Query REST API.
		$curl = curl_init($this->instance_url.$url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth ".$this->access_token, "Content-Type: application/json"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// Only attach POST information if we specified some.
		if(!empty($post))
		{
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		}

		// Query REST API.
		$output = curl_exec($curl);
		curl_close($curl);
		$output = json_decode($output, true);

		// Could we decode the output?
		if(empty($output))
		{
			echo 'Warning: JSON either cannot be decoded or the encoded data is deeper than the PHP recursion limit. ('.__FILE__.', Line '.__LINE__.')';

			return null; // Fail.
		}

		return $output; // Success.
	}

	/**
	* Query Salesforce API.
	* Default SObject URL prepended on queries.
	* @param string $url URL endpoint for API to query.
	* @param array $post POST data to send with API query.
	* @return array Returned data on success. Null on failure.
	*/
	public function QueryObject($url, $post = '')
	{
		$url = '/services/data/v37.0/sobjects/'.$url; // SObject Special.
		$output = $this->Query($url, $post);

		return $output;
	}

	/**
	* Get available versions of Salesforce API.
	* @return array Returned data on success. Null on failure.
	*/
	public function Versions()
	{
		$output = $this->Query("/services/data");

		return $output;
	}

	/**
	* Sample Query to Salesforce API.
	* @return array Returned data on success. Null on failure.
	*/
	public function QuerySample()
	{
		$output = $this->Query("/services/data/v37.0/query?q=".urlencode("SELECT Name from Lead LIMIT 1"));

		return $output;
	}
}
