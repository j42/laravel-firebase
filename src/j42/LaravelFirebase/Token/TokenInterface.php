<?php namespace J42\LaravelFirebase;

interface TokenInterface {

	# Properties
	const FIREBASE_GENERAL = 500;
	const INVALID_OPTION = 503;

	# Methods
	function __construct($secret);
	function create(Array $data, Array $options = null);

}
