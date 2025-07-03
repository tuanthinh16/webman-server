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
use app\controller\v1\UserController;
use app\middleware\AuthMiddleware;
use app\middleware\CorsMiddleware;
use support\Response;
use Webman\Route;



Route::group('/auth/v1', function () {
    Route::post('/register', [UserController::class, 'create']);
    Route::post('/confirm', [UserController::class, 'confirmRegister']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/re-send-otp', [UserController::class, 'reSendOtp']);
})->middleware(CorsMiddleware::class);

Route::post('/test-mail', [\app\controller\v1\MaillerController::class, 'index']);

Route::group('/api', function () {
    Route::group('/v1', function () {

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
