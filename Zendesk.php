<?php namespace LambdaSolutions;

/**
* Zendesk API wrapper class. 
* Easily interact with the Zendesk website.
* Expand on this class with new features as desired.
*
* You can find the Zendesk REST API reference here:
* https://developer.zendesk.com/rest_api/docs/core/introduction
*/
class Zendesk
{	
	// API Endpoints.
	public $url_protocol = 'https://';
	public $url_api = '.zendesk.com/api/v2/';

	// Post-Authentication information.
	public $authenticated = false;

	// Credentials.
	public $username, $subdomain, $token;

	/**
	* Constructor. You may pass in credentials here.
	* @param string $username User.
	* @param string $subdomain User password.
	* @param string $token Security token.
	*/
	public function __construct($username, $subdomain, $token)
	{
		$this->username = $username;
		$this->subdomain = $subdomain;
		$this->token = $token;
	}

	/**
	* Authenticate with Zendesk API.
	* @return bool True on success. False on failure.
	*/
	public function Authenticate()
	{
		return true; // No seperate authentication step for Zendesk.
	}

	/**
	* Query Zendesk API.
	* @param string $url URL endpoint for API to query.
	* @param string $type GET or POST or other. Important for Zendesk API!
	* @param array $data POST data to send with API query.
	* @return array Returned data on success. Null on failure.
	*/
	public function Query($url, $type = 'GET', $data = '')
	{
		// Sanity check.
		if(!isset($this->username) || !isset($this->subdomain) || !isset($this->token))
			return false;

		// Query REST API.
		$curl = curl_init($this->url_protocol.$this->subdomain.$this->url_api.$url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $this->username."/token:".$this->token);
		
		// Only attach POST information if we specified some.
		if(!empty($data))
		{
			$data = json_encode($data);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}

		// Query REST API.
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
		$output = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		// Status 200 to 399 is a success.
		if($status >= 200 && $status < 400)
		{
			$output = json_decode($output, true);

			// Could we decode the output?
			if(empty($output))
			{
				echo 'Warning: JSON either cannot be decoded or the encoded data is deeper than the PHP recursion limit. ('.__FILE__.', Line '.__LINE__.')';
				return false; // Fail.
			}

			return $output; // Success.
		}
		
		echo 'Error: Failed to authenticate with Zendesk. ('.__FILE__.', Line '.__LINE__.')';

		return false; // Fail.
	}
}
