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

use app\helper\JwtHelper;
use app\repositories\user\UserInterface;
use app\repositories\user\UserRepository;
use app\repositories\userIdentity\UserIdentityRepository;
use app\services\auth\AuthService;
use Psr\Container\ContainerInterface;
use app\services\auth\CredentialAuthStrategy;
use app\services\auth\GoogleAuthStrategy;
use app\services\otp\OtpService;

return [
    UserInterface::class => function (ContainerInterface $container) {
        return new UserRepository();
    },
    AuthService::class => function (ContainerInterface $container) {
        return new AuthService([
            $container->get(CredentialAuthStrategy::class),
            $container->get(GoogleAuthStrategy::class),
        ]);
    },
    CredentialAuthStrategy::class => function (ContainerInterface $container) {
        return new CredentialAuthStrategy(
            $container->get(UserRepository::class),
            $container->get(JwtHelper::class)
        );
    },
    GoogleAuthStrategy::class => function (ContainerInterface $container) {
        return new GoogleAuthStrategy(
            $container->get(UserIdentityRepository::class),
            $container->get(JwtHelper::class),
            $container->get(OtpService::class)
        );
    },
    UserRepository::class => fn() => new UserRepository(),
    UserIdentityRepository::class => fn() => new UserIdentityRepository(),
    JwtHelper::class => fn() => new JwtHelper(),
    OtpService::class => fn() => new OtpService(),
];
