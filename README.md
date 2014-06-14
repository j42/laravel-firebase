laravel-firebase
================

A Firebase port for Laravel (4.2+)


##Configuration

Install via composer.  If you have `minimum-stability` set to `stable`, you should add a `@beta` or `@dev` in order to use the `php-jwt` library (a dependency managed by firebase for generating JSON web token).

Add the following line to your `composer.json` and run composer update:

	{
	  "require": {
	    "j42/laravel-firebase": "dev-master"
	  }
	}

Then add the service providers and facades to `config/app.php`

	'J42\LaravelFirebase\LaravelFirebaseServiceProvider',

...

	'Firebase'		  => 'J42\LaravelFirebase\LaravelFirebaseFacade'


###Access Tokens

Finally, you should configure your firebase connection in the `config/database.php` array.  There are two ways you can define this:

####Simple Access Token**

```php
'firebase' => array(
	'host'		=> 'https://<you>.firebaseio.com/',
	'token'		=> '<yoursecret>',
	'timeout'	=> 10
)
```

####Advanced: Request a JWT**

This accepts any of the standard options allowed by the firebase [security rules](https://www.firebase.com/docs/security/security-rules.html) and will generate a JSON Web Token for more granular authentication (subject to auth security rules and expirations).

```php
'firebase' => array(
	'host'		=> 'https://servicerunner.firebaseio.com/',
	'token'		=> [
		'secret'	=> '<yoursecret>',
		'options'	=> [
			'auth'	=> [
				'email' => 'example@yoursite.com'
			]
		],
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

// Returns: (Array) Firebase response
Firebase::set('/my/path', $data);

// Returns: (Array) Firebase response
Firebase::push('/my/path', $data);

// Returns: (Array) Firebase response
Firebase::delete('/my/path');
```


##Advanced Use

Create a token manually:

```php
$FirebaseTokenGenerator = new J42\LaravelFirebase\FirebaseToken(FIREBASE_SECRET);
$Firebase = App::make('firebase');

$token = $FirebaseTokenGenerator->create($data, $options);

$Firebase->setToken($token);
```