<?php namespace J42\LaravelFirebase;

use Illuminate\Support\ServiceProvider;

class LaravelFirebaseServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->package('j42/laravel-firebase', 'firebase');
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
		App::singleton('firebase', function($app) {
			return new FirebaseClient($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['firebase'];
	}

}
