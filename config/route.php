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

use app\controller\v1\AuthController;
use app\controller\v1\OrderController;
use app\controller\v1\TransactionController;
use app\controller\v1\UserController;
use app\controller\v1\WalletController;
use app\middleware\AuthMiddleware;
use app\middleware\CorsMiddleware;
use support\Response;
use Webman\Route;

Route::post('/auth/v1/login', [AuthController::class, 'login'])->middleware(CorsMiddleware::class);
Route::get('/testview',      [UserController::class, 'testview']);


Route::group('/api', function () {
    Route::group('/v1', function () {
        Route::group('/orders', function () {
            Route::get('',                      [OrderController::class, 'getByUserID']);
            // POST /api/orders/place
            Route::post('/place',               [OrderController::class, 'place']);
            // POST /api/orders/accept?order_id={order_id}
            Route::post('/accept',              [OrderController::class, 'accept']);
            // POST /api/orders/close?order_id={order_id}
            Route::post('/close',               [OrderController::class, 'close']);
            // POST /api/orders/cancel?order_id={order_id}
            Route::post('/cancel',            [OrderController::class, 'cancel']);
        });
        Route::group('/transaction', function () {
            Route::post('', [TransactionController::class, 'create']);
            Route::get('', [TransactionController::class, 'index']);
        });
        Route::group('/wallet', function () {
            Route::get('', [WalletController::class, 'getBalance']);
        });
        Route::group('/users', function () {
            require base_path('app/routes/UserRoute.php');
        });
    });
})->middleware([AuthMiddleware::class, CorsMiddleware::class]);

//view
Route::get('/test', function () {
    $file = public_path() . '/mail-service.html';
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
