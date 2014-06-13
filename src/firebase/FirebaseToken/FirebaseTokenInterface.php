<?php namespace J42\LaravelFirebase;

interface FirebaseToken {

	# Properties
	const FIREBASE_GENERAL = 500;
	const INVALID_OPTION = 503;
	protected $secret;

	# Methods
	public function __construct($secret);
	public function create(Array $data, Array $options = null);
	private static function configure(Array $options);
	private static function error($n);

}