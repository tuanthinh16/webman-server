<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\controller\AuthController;
use app\controller\OrderController;
use app\controller\TransactionController;
use app\controller\UserController;
use app\controller\WalletController;
use app\middleware\AuthMiddleware;
use support\Response;
use Webman\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/api/user', [UserController::class, 'show']);
Route::post('/api/users/register',      [UserController::class, 'register']);


//users
Route::group('/api/users', function () {
    Route::get('', [UserController::class, 'index']);
    Route::post('password',      [UserController::class, 'changePassword']);
    Route::patch('last_login',    [UserController::class, 'updateLastLogin']);
}, [
    'middleware' => [
        AuthMiddleware::class
    ]
]);

//orders
Route::group('/api/orders', function () {
    // GET  /api/orders
    Route::get('',                      [OrderController::class, 'getByUserID']);
    // POST /api/orders/place
    Route::post('/place',               [OrderController::class, 'place']);
    // POST /api/orders/accept?order_id={order_id}
    Route::post('/accept',              [OrderController::class, 'accept']);
    // POST /api/orders/close?order_id={order_id}
    Route::post('/close',               [OrderController::class, 'close']);
    // POST /api/orders/cancel?order_id={order_id}
    Route::post('/cancel',            [OrderController::class, 'cancel']);
}, [
    'middleware' => [
        AuthMiddleware::class,
    ],
]);
//Transaction


Route::group('/api/transaction', function () {
    Route::post('', [TransactionController::class, 'create']);
    Route::get('', [TransactionController::class, 'index']);
});
// wallet 
Route::group('/api/wallet', function () {
    Route::get('', [WalletController::class, 'getBalance']);
});

//view
Route::get('/test', function () {
    $file = public_path() . '/socket.html';
    if (! is_file($file)) {
        return new Response(404, ['Content-Type' => 'text/plain']);
    }
    $html = file_get_contents($file);
    return new Response(200, ['Content-Type' => 'text/html'], $html);
});
Route::get('/', function () {
    $file = public_path() . '/index.html';
    if (! is_file($file)) {
        return new Response(404, ['Content-Type' => 'text/plain']);
    }
    $html = file_get_contents($file);
    return new Response(200, ['Content-Type' => 'text/html'], $html);
});
Route::disableDefaultRoute();
