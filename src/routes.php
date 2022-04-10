<?php
Route::group(['middleware' => ['api'], 'prefix' => env('EASY_API_BASE_URL', 'easy-api'), 'namespace' => 'DevsRyan\LaravelEasyApi\Controllers'], function() {
    //EasyApi Routes
    Route::get('/', 'AdminController@home');
    Route::get('{model}', 'AdminController@index');
    Route::get('{model}/{id}', 'AdminController@show');
});
