<?php namespace J42\LaravelFirebase;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;


class Client {

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

		// Http client
		$this->http = new Client();

	}


	// Return: (json) Firebase Response
	// Args: (string) $path, (mixed) $data
	public function __call($func, $args) {

		// Errors
		if (!in_array($func, $this->passthrough)) throw new \UnexpectedValueException('Unexpected method called');
		if (count($args) < 1) throw new \UnexpectedValueException('Not enough arguments');

		// Process URL/Path
		$url = $this->absolutePath($args[0]);

		// Write Methods
		switch ($func) {

			case 'get':
				// Read Data
				$requestType = 'GET';
				return $this->read($url, (isset($args[1]) ? $args[1] : false));
				break;

			case 'set': $requestType = 'PUT'; break;
			case 'push': $requestType = 'POST'; break;
			case 'update': $requestType = 'PATCH'; break;
		}

		// Else Write Data
		return ($requestType) ? $this->write($url, $args[1], $requestType) : null;

	}




	// Return: (json) Firebase Response
	// Args: void
	public function setWithPriority($path, Array $data, $priority) {
		$url = $this->absolutePath($path);
		$data['.priority'] = $priority;
		// Return Response
		return $this->write($url, $data, 'PUT');
	}

	// Return: (Guzzle) Firebase Response
	// Args: (string) $path
	public function delete($path) {
		// Process Request
		$url = $this->absolutePath($path);
		$request  = $this->http->request('DELETE', $url, []);
		return $this->validateResponse($request)->getBody();
	}


	// Return: (Array) Firebase Response || (Illuminate\Database\Eloquent\Collection) Eloquent Collection
	// Args: (string) $path
	public function read($path, $eloquentCollection = false) {

		// Process Request
		$request  = $this->http->request('GET', $path, []);
		$response = $this->validateResponse($request)->getBody();

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
			$ids[] = self::getId($object);
		}

		// Return Collection
		return call_user_func_array($eloquentModel.'::whereIn', ['_id', $ids]);
	}


	// Return: (Guzzle) Firebase Response
	// Args: (string) $path, (Array || Object) $data, (string) $method
	public function write($path, $data, $method = 'PUT') {

		// Sanity Check
		if (is_object($data)) $data = $data->toArray();

		// JSON.stringify $data
		$json = json_encode($data);

		// Sanity Check
		if ($json === 'null') throw new \UnexpectedValueException('Data Error: Invalid json/write request');

		// Format Request
		$cleaned  = self::clean($data);
		if (is_array($cleaned) && !isset($cleaned['.priority'])) {
			$cleaned['.priority'] = time();
		}

		// Process Request
		$request = $this->http->request($method, $path, ['json' => $cleaned]);

		// Is Response Valid?
		return $this->validateResponse($request)->getBody();
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
			$this->token = $token;
		} else {
			throw new \UnexpectedValueException('Token was not a valid configuration array (secret, options[, data]) or string');
		}
	}


	// Return: (string) Absolute URL Path
	// Args: (mixed) $path
	private function absolutePath($item) {

		// Sanity Check
		if (!is_string($item) && !$item instanceOf Model) throw new \UnexpectedValueException('Path should be a string or object.');

		// Item is already a fully-qualified URL
		if ((strpos($item, 'https://') !== false)) return $item;

		// Else, build URL
		$url  = $this->host;

		// Path from Item
		if (is_string($item)) $path = ltrim($item, '/');
		if ($item instanceOf Model) $path = strtolower(get_class($item)).'s/'.self::getId($item);

		// Return URL
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



	// [STA]
	// Return: (mixed) $data
	// Args: (mixed) $data
	public static function clean($data) {
		// String?
		if (!is_array($data)) return $data;
		// Needs a good scrubbing...
		$out = [];
		$whitelist = ['.priority'];
		// Recursive iterator to sanitize all keys (and flatten object values)
		foreach ($data as $key => $value) {
			$key = (in_array($key, $whitelist) !== false) ? $key : preg_replace('/[\.\#\$\/\[\]]/i', '', $key);
			if (is_object($value)) $value = $value->__toString();
			if (is_array($value) || is_object($value)) {
				$out[$key] = self::clean($value);
			} else {
				$out[$key] = $value;
			}
		}
		return $out;
	}


	// [STA]
	// Return: (string) Model ID
	// Args: (Model) $obj
	public static function getId($obj) {
		// Valid Eloquent Model Inheritance?
		if (method_exists($obj, 'getKey') && $key = $obj->getKey()) {
			if (!empty($key)) return $obj->getKey();
		}
		// Catch Generic
		if (isset($obj['id'])) return $obj['id'];
		if (isset($obj['_id'])) return $obj['_id'];
		if (isset($obj['$id'])) return $obj['$id'];
		// Else
		throw new \UnexpectedValueException('Invalid model object received: no primary ID (id, _id, $id)');
 	}

}
