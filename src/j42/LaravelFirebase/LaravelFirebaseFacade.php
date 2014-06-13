<?php namespace J42\LaravelFirebase;

use Illuminate\Support\Facades\Facade;

class Firebase extends Facade {

    protected static function getFacadeAccessor() { return 'firebase'; }

}