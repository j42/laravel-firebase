<?php namespace J42\LaravelFirebase;

class Token implements TokenInterface {

	// Return: FirebaseToken
	// Args: (string) $secret
	public function __construct($secret) {
		if (!is_string($secret)) throw new \UnexpectedValueException('Secret must be a string.');
		$this->secret = $secret;
	}


	// Return: (string) JSON Web Token
	// Args: (Array) $data, (Array) $options
	/*	$options
			[admin] Bypass all security rules? (Default: false)
	*
		    [debug] Enable debug output from security rules? (Default: false)
	*
		    [expires] (int || DateTime) When token should expire
	*
		    [notBefore] (int || DateTime) Only valid after this time
    */
	public function create(Array $data, Array $options = null) {

		$json 			= json_encode($data);
		$jsonError 		= (function_exists("json_last_error") && $errno = json_last_error());
		$jsonInvalid	= ($json === 'null');

		// Handle Errors
		if ($jsonError) static::error($errno); elseif ($jsonInvalid) static::error(JSON_ERROR_SYNTAX);

		// Build Claims
		$claims = (is_array($options)) ? $this->configure($options) : [];
		$claims += [
			'd'		=> $data,
			'v'		=> 0.1,
			'iat'	=> time()
		];

		// Return JWT
		return \JWT::encode($claims, $this->secret, 'HS256');

	}


	// [STA]
	// Return: (Array) JSON Web Token Meta-Options
	// Args: (Array) $config
	private static function configure(Array $options) {
		$claims = [];
		foreach ($options as $key => $value) {

			// Parse Options
			switch ($key) {
				case 'admin': $claims['admin'] = $value; break;
				case 'debug': $claims['debug'] = $value; break;
				case 'expires':
				case 'notBefore':
					$code = ($key === 'notBefore') ? 'nbf' : 'exp';

					// (DateTime || int) ?
					switch(gettype($value)) {
						case 'integer':
							$claims[$code] = $value;
							break;
						case 'object':
							if ($value instanceOf \DateTime) $claims[$code] = $value->getTimestamp(); else $this->error(403);
							break;
						default:
							static::error(403);
					}
					break;

				default: static::error(403);
			}

		}
		return $claims;
	}


	// [STA]
	// Return: void
	// Args: (int) $errno
	private static function error($n) {

		$messages = [
			JSON_ERROR_DEPTH 	 		=> 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR 		=> 'Unexpected control character found',
            JSON_ERROR_SYNTAX 	 		=> 'Syntax error, malformed JSON',
            static::FIREBASE_GENERAL	=> 'Firebase encountered an unknown error',
            static::INVALID_OPTION		=> 'The Token Configuration Service encountered an invalid option in the configuration'
		];

		throw new \UnexpectedValueException($messages[$n] ?: 'Unknown Error: '.$n);
	}

}
