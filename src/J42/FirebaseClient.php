<?php namespace J42\LaravelFirebase;



class FirebaseClient {

	# Properties
	private $token;


	// Return: (obj) FirebaseClient
	// Args: (Array) $config
	public function __construct(Array $config) {

		// Valid host?
		if (empty($config['host'])) throw new UnexpectedValueException('Please enter a valid Firebase host URL');

		// Set Secret
		$this->setToken($config['token'] ?: null);

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

}