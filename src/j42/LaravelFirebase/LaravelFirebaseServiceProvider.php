<?php namespace J42\LaravelFirebase;

use Event;
use Illuminate\Support\ServiceProvider;


class LaravelFirebaseServiceProvider extends ServiceProvider {


	/**
	 * Indicates if loading of the provider is deferred.
	 * @var bool
	 */
	protected $defer = false;



	/**
	 * Bootstrap the application events.
	 * @return void
	 */
	public function boot() {

		// Reference
		$self = $this;

		// Register Eloquent Hooks
		Event::listen('eloquent.created: *', function($obj) use ($self) { return $self->sync($obj); }, 10);
		Event::listen('eloquent.updated: *', function($obj) use ($self) { return $self->sync($obj); }, 10);
		Event::listen('eloquent.deleted: *', function($obj) use ($self) { return $self->delete($obj); }, 10);
	}



	/**
	 * Register the service provider.
	 * @return void
	 */
	public function register() {

		// Register Package Configuration
		$this->publishes([
			__DIR__.'/../../../config/firebase.php'	=> config_path('firebase.php')
		], 'config');

		// Get Connection
		$config = (!empty(config('firebase')))
					? config('firebase')
					: config('database.connections.firebase');

		// Root provider
		$this->app->singleton('firebase', function($app) use ($config) {
			return new Client($config);
		});

		// Token Provider
		$this->app->bind('firebase.token', function($app) use ($config) {
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


	/**
	 * Sync handler
	 * @param  object $obj
	 * @return void
	 */
	private function sync($obj) {

		$sync = (!empty(config('firebase')))
				 ? config('firebase.sync')
				 : config('database.connections.firebase.sync');	// `sync` by Default (config)?
		$path = strtolower(get_class($obj)).'s';					// plural collection name
		$id   = \Firebase::getId($obj);								// object ID (extracted)

		// Whitelist
		if (isset($obj->firebase) && !empty($obj->firebase) && is_array($obj->firebase)) {
			$data = [];
			foreach ($obj->toArray() as $key => $value) {
				// Filter Attributes
				if (in_array($key, $obj->firebase) !== false) $data[$key] = $value;
			}
		} else {
			$data = $obj->toArray();
		}

		// Post if Allowed
		if ((($sync !== false || !empty($obj->firebase)) && $obj->firebase !== false) || $obj->firebase === true) {
			\Firebase::set('/'.$path.'/'.$id, $data);
		}

		return true;
	}


	/**
	 * Delete handler
	 * @param  object $obj
	 * @return void
	 */
	private function delete($obj) {

		$sync = (!empty(config('firebase')))
				 ? config('firebase.sync')
				 : config('database.connections.firebase.sync');	// `sync` by Default (config)?
		$path = strtolower(get_class($obj)).'s';					// plural collection name
		$id   = \Firebase::getId($obj);								// object ID (extracted)

		// Delete if Allowed
		if ($sync !== false || !empty($obj->firebase)) {
			\Firebase::delete('/'.$path.'/'.$id);
		}
	}


}
