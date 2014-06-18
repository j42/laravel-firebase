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


Access Tokens
----

Finally, you should configure your firebase connection in the `config/database.php` array.  There are two ways you can define this:

####Simple Access Token

```php
'firebase' => array(
	'host'		=> 'https://<you>.firebaseio.com/',
	'token'		=> '<yoursecret>',
	'timeout'	=> 10,
	'sync'		=> false,			// OPTIONAL: auto-sync all Eloquent models with Firebase?
)
```

####Advanced: Request a JWT

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
	'timeout'	=> 10,
	'sync'		=> false,			// OPTIONAL: auto-sync all Eloquent models with Firebase?
)
```


The **FirebaseClient** instance is loaded into the IoC container as a singleton, containing a Guzzle instance used to interact with Firebase.


Getting Started
----

Making simple get requests:

```php
// Returns: (Array) of data items
Firebase::get('/my/path');

// Returns: (\Illuminate\Database\Eloquent\Collection) Eloquent collection of Eloquent models
Firebase::get('/my/path', 'ValidEloquentModelClass');

// Returns: (\Illuminate\Database\Eloquent\Model) Single Eloquent model
// Conditions: $SomeModelInstance must inherit from Eloquent at some point, and have a (id, _id, or $id) property
Firebase::get($SomeModelInstance);


// Returns: (Array) Firebase response
Firebase::set('/my/path', $data);

// Returns: (Array) Firebase response
Firebase::push('/my/path', $data);

// Returns: (Array) Firebase response
Firebase::delete('/my/path');
```


Model Syncing
----

By default this package will keep your Eloquent models in sync with Firebase.  That means that whenever `eloquent.updated: *` is fired, the model will be pushed to Firebase.  

This package will automatically look for 'id', '_id', and '$id' variables on the model so that Firebase paths are normalized like so:

```php

// Eloquent model: User
// Firebase location: /users/{user::id}

$User = new User(['name' => 'Julian']);

$User->save();	// Pushed to firebase

$Copy = Firebase::get('/users/'.$User->id, 'User'); 	// === copy of $User
$Copy = Firebase::get($User);							// === copy of $User

```

To disable this, please ensure `'sync' => false` in your database.connections.firebase configuration array.

This works with any package that overwrites the default Eloquent model SO LONG AS it is configured to fire the appropriate `saved` and `updated` events.  At the moment it is tested with the base `Illuminate...Model` as well as the [Jenssegers MongoDB Eloquent Model](https://github.com/jenssegers/laravel-mongodb)

####Syncing Models Individually

If you want to add a whitelist of properties to push to firebase automatically whenever a model is **updated**, you can do so by adding a whitelist of properties to any supported model.

This action happens regardless of the (automatic) `sync` property in your configuration array.  If the `$firebase` whitelist array is found, then the fields contained will be posted on every update event.

```php

class User extends Eloquent {
	...
	public $firebase = ['public_property','name','created'];	// These properties are pushed to firebase every time the model is updated
}

```


##Advanced Use

#####Create a token manually

```php
$FirebaseTokenGenerator = new J42\LaravelFirebase\FirebaseToken(FIREBASE_SECRET);
$Firebase = App::make('firebase');

$token = $FirebaseTokenGenerator->create($data, $options);

$Firebase->setToken($token);
```