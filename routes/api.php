<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/sub/{category}', 'HomeController@sendData');
Route::get('/sub/{category_slug}/filterBrands/{brands}', 'ProductController@filterBrands');
Route::get('/sub/{category_slug}/filterCondition/{is_used}', 'ProductController@filterCondition');
