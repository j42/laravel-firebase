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
		$this->package('j42/laravel-firebase', 'firebase');

		// Register Eloquent Hooks
		$sync = Config::get('database.connections.firebase.sync');
		Event::listen('eloquent.updated: *', function($obj) {
			$path = strtolower(get_class($obj)).'s';
			$id   = \Firebase::getId($obj);
			$data = $obj->toArray();
			// Whitelist
			if (!empty($obj->firebase)) {
				$data = [];
				foreach ($obj->getAttributes() as $key => $value) {
					// Filter Attributes
					if (in_array($key, $obj->firebase) !== false) $data[$key] = $value;
				}
			}
			// Post if Allowed
			if ($sync !== false || !empty($obj->firebase)) {
				\Firebase::set('/'.$path.'/'.$id, $data);
			}
			return true;
		}, 10);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Get pseudo-connection from config
		$config = Config::get('database.connections.firebase');

		// Bind `firebase` to IoC
		App::singleton('firebase', function($app) use ($config) {
			return new FirebaseClient($config);
		});

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['firebase'];
	}

}
