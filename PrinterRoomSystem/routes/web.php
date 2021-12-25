<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/','MainController@index');
Route::post('/','MainController@submitForm');
Route::get('/accountSetting','MainController@getAccountSetting');
Route::get('/contact','MainController@getContact');
Route::post('/changePassword','MainController@changePassword');
Route::get('/Report','MainController@getReport');
Route::get('/login','LoginController@index');
Route::post('/login','LoginController@login');
Route::get('/logout','LoginController@logout');
Route::get('/admin','AdminController@index');
Route::get('/admin/history','AdminController@history');
Route::post('/admin/addUser','AdminController@addUser');
Route::post('/admin/editUser','AdminController@editUser');
Route::post('/admin/deleteUser','AdminController@deleteUser');
Route::post('/admin/searchUser','AdminController@searchUser');
Route::post('/admin/getUserDataList','AdminController@getUserDataList');
Route::post('/admin/getOrganizationDataList','AdminController@getOrganizationDataList');
Route::post('/admin/getHistory','AdminController@getHistory');
Route::post('/admin/getHistoryMaxPage','AdminController@getHistoryMaxPage');
Route::post('/admin/getOrganizationReport','AdminController@getOrganizationReport');
Route::get('/admin/organizationReport','AdminController@organizationReport');
Route::post('/admin/clearHistory','AdminController@clearOrderHistory');
Route::get('/admin/js/{jsFileName}','AdminController@getJS');
