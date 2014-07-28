<?php namespace J42\LaravelFirebase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

class LaravelFirebaseServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {

		// Register Package
		$this->package('j42/laravel-firebase', null, __DIR__.'/../../../');

		// Reference
		$self = $this;

		// Register Eloquent Hooks
		Event::listen('eloquent.created: *', function($obj) use ($self) { return $self->sync($obj); }, 10);
		Event::listen('eloquent.updated: *', function($obj) use ($self) { return $self->sync($obj); }, 10);
		Event::listen('eloquent.deleted: *', function($obj) use ($self) { return $self->delete($obj); }, 10);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Get pseudo-connection from config
		$config = Config::get('database.connections.firebase');

		// Dependency Injection: Main Service
		App::singleton('firebase', function($app) use ($config) {
			return new Client($config);
		});

		// Dependency Injection: Token Provider
		App::bind('firebase.token', function($app) use ($config) {
			return new Token($config['token']);
		});

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['firebase', 'firebase.token'];
	}


	// Process Sync Event
	// Returns: true
	// Arguments: (Model) $obj
	private function sync($obj) {
		$sync = Config::get('database.connections.firebase.sync');	// `sync` by Default (config)?
		$path = strtolower(get_class($obj)).'s';					// plural collection name
		$id   = \Firebase::getId($obj);								// object ID (extracted)

		// Whitelist
		if (isset($obj->firebase) && !empty($obj->firebase) && is_array($obj->firebase)) {
			$data = [];
			foreach ($obj->toArray() as $key => $value) {
				// Filter Attributes
				if (in_array($key, $obj->firebase) !== false) $data[$key] = $value;
			}
		} else $data = $obj->toArray();

		// Post if Allowed
		if ((($sync !== false || !empty($obj->firebase)) && $obj->firebase !== false) ||
			$obj->firebase === true) {
			\Firebase::set('/'.$path.'/'.$id, $data);
		}

		return true;
	}

	// Process Delete Event
	// Returns: true
	// Arguments: (Model) $obj
	private function delete($obj) {
		$sync = Config::get('database.connections.firebase.sync');	// `sync` by Default (config)?
		$path = strtolower(get_class($obj)).'s';					// plural collection name
		$id   = \Firebase::getId($obj);								// object ID (extracted)
		// Delete if Allowed
		if ($sync !== false || !empty($obj->firebase)) {
			\Firebase::delete('/'.$path.'/'.$id);
		}
	}


}
