<?php namespace J42\LaravelFirebase;

use \Illuminate\Database\Eloquent\Collection;

class FirebaseClient {

	# Properties
	private $http;
	private $timeout;
	private $token;
	private $host;

	private $passthrough = ['set','push','update','get'];


	// Return: (obj) FirebaseClient
	// Args: (Array) $config
	public function __construct(Array $config) {

		// Valid host?
		if (empty($config['host'])) throw new \UnexpectedValueException('Please enter a valid Firebase host URL.');
		if (strpos($config['host'], 'http://') > -1) throw new \UnexpectedValueException('Please use HTTPS for all Firebase URLs.');

		// Set Host URI
		$this->setHost($config['host']);

		// Set Secret
		$this->setToken($config['token'] ?: null);

		// Set Timeout
		$this->setTimeout($config['timeout'] ?: 10);

		// Set Client
		$this->http = new \GuzzleHttp\Client();

	}


	// Return: (json) Firebase Response
	// Args: (string) $path, (mixed) $data
	public function __call($func, $args) {

		// Errors
		if (!in_array($func, $this->passthrough)) throw new \UnexpectedValueException('Unexpected method called');
		if (count($args) < 1) throw new \UnexpectedValueException('Not enough arguments');

		// Process URL/Path
		$url = (strpos($args[0], 'https://') === false) ? $this->absolutePath($args[0]) : $args[0];

		// Write Methods
		switch ($func) {

			case 'get':
				// Read Data 
				$requestType = 'GET'; 
				return $this->read($url, (isset($args[1]) ? $args[1] : false));
			break;

			case 'delete':
				// Delete Data
				$requestType = 'DELETE';
				return $this->delete($url);
			break;

			case 'set': $requestType = 'PUT'; break;
			case 'push': $requestType = 'POST'; break;
			case 'update': $requestType = 'PATCH'; break;
		}

		// Else Write Data
		return ($requestType) ? $this->write($url, $args[1], $requestType) : null;

	}


	// Return: (Guzzle) Firebase Response
	// Args: (string) $path
	public function delete($path) {

		// Process Request
		$request  = $this->http->createRequest('DELETE', $path);
		$response = $this->http->send($request);
		return $this->validateResponse($response)->json();
	}


	// Return: (Guzzle) Firebase Response
	// Args: (string) $path
	public function read($path, $eloquentCollection = false) {

		// Process Request
		$request  = $this->http->createRequest('GET', $path);
		$response = $this->http->send($request);
		$response = $this->validateResponse($response)->json();

		// Is Response Valid?
		return ($eloquentCollection) ? $this->makeCollection($response, $eloquentCollection) : $response;
	}


	// Return: (Illuminate\Database\Eloquent\Collection) Eloquent Collection
	// Args: (Array) $response, (string) $eloquentModel
	public function makeCollection(Array $response, $eloquentModel) {

		// Sanity Check
		if (!class_exists($eloquentModel)) return Collection::make($response);

		// Get IDs
		$ids = [];
		foreach ($response as $id => $object) {
			$ids[] = $id;
			if (isset($object['_id'])) $ids[] = $object['_id'];
			if (isset($object['id'])) $ids[] = $object['id'];
		}

		// Return Collection
		return call_user_func_array($eloquentModel.'::whereIn', ['_id', $ids]);
	}


	// Return: (Guzzle) Firebase Response
	// Args: (string) $path, (Array || Object) $data, (string) $method
	public function write($path, $data, $method = 'PUT') {

		// Sanity Checks
		if (!is_array($data) && !is_object($data)) throw new \UnexpectedValueException('Invalid input type received');
		if ($json === 'null') throw new \UnexpectedValueException('HTTP Error: Invalid request (invalid JSON)');

		// Typecase Data to JSON
		$json = json_encode((array) $data);

		// Process Request
		$request  = $this->http->createRequest($method, $path, ['body' => $json]);
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', strlen($json));
		$response = $this->http->send($request);

		// Is Response Valid?
		return $this->validateResponse($response)->json();
	}


	// Return: void
	// Args: (string) $host
	public function setHost($host) {
		$host .= (substr($host,-1) === '/') ? '' : '/';
		$this->host = $host;
	}


	// Return: void
	// Args: (string) $timeout
	public function setTimeout($timeout) {
		if (is_numeric($timeout)) $this->timeout = $timeout;
	}


	// Return: void
	// Args: (string || array || bool) $token
	public function setToken($token) {

		// Token is Array('secret','options')
		if (is_array($token) && isset($token['secret']) && isset($token['options'])) {
			// Generate Firebase JSON Web Token
			$FirebaseToken 	= new FirebaseToken($token['secret']);
			$FirebaseData	= $token['data'] ?: [];
			$LocalData	    = [
				'user'	=> App::environment()
			];

			// Set Token
			$this->token = $FirebaseToken->create($LocalData + $FirebaseData, $token['options']);

		} elseif (is_string($token)) {
			// Token is a string secret
			$this->secret = $token;
		} else {
			throw new \UnexpectedValueException('Token was not a valid configuration array (secret, options[, data]) or string');
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


	// Return: (Guzzle) Response
	// Args: (Guzzle) Response
	private function validateResponse($response) {
		if ($response->getStatusCode() == 200) {
			return $response;
		} else throw new \Exception('HTTP Error: '.$response->getReasonPhrase());
	}

}