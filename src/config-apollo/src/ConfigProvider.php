<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\ConfigApollo;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ClientInterface::class => ClientFactory::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for apollo.',
                    'source' => __DIR__ . '/../publish/apollo.php',
                    'destination' => BASE_PATH . '/config/autoload/aliyun_acm.php',
                ],
            ],
        ];
    }
}
