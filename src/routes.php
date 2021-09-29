<?php
Route::group(['middleware' => ['api'], 'prefix' => env('EASY_API_BASE_URL', 'easy-api'), 'namespace' => 'DevsRyan\LaravelEasyApi\Controllers'], function() {
    //EasyApi Routes
    Route::get('/', 'AdminController@home');
    Route::get('{model}/index', 'AdminController@index');
    Route::post('{model}', 'AdminController@store');
    Route::put('{model}/{id}', 'AdminController@update');
    Route::patch('{model}/{id}', 'AdminController@update');
    Route::delete('{model}/{id}', 'AdminController@destroy');
});
