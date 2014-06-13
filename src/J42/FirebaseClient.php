<?php namespace J42\LaravelFirebase;



class FirebaseClient {

	# Properties
	private $http;
	private $token;
	private $host;

	private $passthrough = ['set','push','update','get'];


	// Return: (obj) FirebaseClient
	// Args: (Array) $config
	public function __construct(Array $config) {

		// Valid host?
		if (empty($config['host'])) throw new UnexpectedValueException('Please enter a valid Firebase host URL');

		// Set Host URI
		$this->setHost($config['host']);

		// Set Secret
		$this->setToken($config['token'] ?: null);

		// Set Timeout
		$this->setTimeout($config['timeout'] ?: 10);

		// Set Client
		$this->http = new \GuzzleHttp\Client();

	}


	// Return: (Array) Firebase Response
	// Args: (string) $path, (mixed) $data
	public function __call($func, $args) {

		// Errors
		if (!in_array($func, $this->passthrough)) throw new UnexpectedValueException('Unexpected method called');
		if (count($args) < 1) throw new UnexpectedValueException('Not enough arguments');

		// Write Methods
		switch ($func) {
			case 'set': $requestType = 'PUT'; break;
			case 'push': $requestType = 'POST'; break;
			case 'update': $requestType = 'PATCH'; break;
			case 'get': $requestType = 'GET'; break;
		}

		if (count($args) < 3 && $func !== 'get') {
			// Write Data
			$this->write($func, $args[1], $requestType);
		} else {
			// Read Data
			$this->get($func);
		}

	}
	public function set($path, $data) { return $this->writeData($path, $data) }


	// Return: void
	// Args: (string) $host
	public function setHost($host) {
		$host .= (substr($host,-1) === '/') ? '' : '/';
		$this->host = $host;
	}


	// Return: void
	// Args: (string || array || bool) $token
	public function setToken($token) {

		// Token is Array('secret','options')
		if (is_array($token) && isset($token['secret']) && isset($token['options'])) {
			// Generate Firebase JSON Web Token
			$FirebaseToken 	= new FirebaseToken($token['secret']);
			$FirebaseData	= $token['data'] ?: [];
			$LocalData		= [
				'user'	=> App::environment()
			];

			// Set Token
			$this->token = $FirebaseToken->create($LocalData + $FirebaseData, $token['options']);

		} elseif (is_string($token)) {
			// Token is a string secret
			$this->secret = $token;
		} else {
			throw new UnexpectedValueException('Token was not a valid configuration array (secret, options[, data]) or string');
		}
	}


	// Return: (string) Absolute URL Path
	// Args: (string) $path
	private function absolutePath($path) {
		$url  = $this->host;
		$path = ltrim($path, '/');
		$auth = (!empty($this->token)) ? '?'.http_build_query(['auth' => $this->token]) : '';

		return $url.$path.'.json'.$auth;
	}

}