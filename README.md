laravel-firebase
================

A Firebase port for Laravel (4.2+)


##Configuration

Install via composer.  If you have `minimum-stability` set to `stable`, you should add a `@beta` or `@dev` in order to use the `php-jwt` library (a dependency managed by firebase for generating JSON web token).

	"j42/laravel-firebase": "dev-master"

Then add the service providers and facades to `config/app.php`

	'J42\LaravelFirebase\LaravelFirebaseServiceProvider',

...

	'Firebase'		  => 'J42\LaravelFirebase\LaravelFirebaseFacade'

Finally, you should configure your firebase connection in the `config/database.php` array.  There are two ways you can define this:

**Simple Access Token**

	```php
	'firebase' => array(
		'host'		=> 'https://<you>.firebaseio.com/',
		'token'		=> '<yoursecret>',
		'timeout'	=> 10
	)
	```

**Request a JWT**

	```php
	'firebase' => array(
		'host'		=> 'https://servicerunner.firebaseio.com/',
		'token'		=> [
			'secret'	=> '<yoursecret>',
			'options'	=> null,
			'data'		=> []
		],
		'timeout'	=> 10
	)
	```


The **LaravelFirebase** service is loaded into the IoC container as a singleton, containing a Guzzle instance used to interact with Firebase.



##Getting Started

Making simple get requests:

	```php
	// Returns: (Array) of data items
	Firebase::get('/my/path');

	// Returns: (\Illuminate\Database\Eloquent\Collection) Eloquent collection of Eloquent models
	Firebase::get('/my/path', 'ValidEloquentModelClass');
	```