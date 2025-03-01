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

namespace HyperfTest\Config;

use Hyperf\Utils\Arr;
use HyperfTest\Config\Stub\FooConfigProvider;
use HyperfTest\Config\Stub\ProviderConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ProviderConfigTest extends TestCase
{
    public function testProviderConfigMerge()
    {
        $c1 = [
            'listeners' => ['L1'],
            'dependencies' => [
                'D1' => 'D1',
                'D2' => 'D2',
            ],
        ];

        $c2 = [
            'listeners' => ['L2'],
            'dependencies' => [
                'D1' => 'D1',
                'D2' => 'D3',
            ],
        ];

        $c3 = [
            'listeners' => ['L2'],
            'dependencies' => [
                'D1' => 'D1',
                'D3' => 'D3',
                'D4' => 'D4',
            ],
        ];

        $result = ProviderConfig::merge($c1, $c2, $c3);

        $this->assertSame(['D1' => 'D1', 'D2' => 'D3', 'D3' => 'D3', 'D4' => 'D4'], $result['dependencies']);
    }

    public function testProviderConfigNotHaveDependencies()
    {
        $c1 = [
            'listeners' => ['L1'],
            'dependencies' => [
                'D1' => 'D1',
                'D2' => 'D2',
            ],
        ];

        $c2 = [
            'listeners' => ['L2'],
        ];

        $result = ProviderConfig::merge($c1, $c2);
        $this->assertSame(['D1' => 'D1', 'D2' => 'D2'], $result['dependencies']);
        $this->assertSame(['L1', 'L2'], $result['listeners']);
    }

    public function testProviderConfigHaveNull()
    {
        $c1 = [
            'listeners' => ['L1'],
        ];

        $c2 = [
            'listeners' => [value(function () {
                return null;
            })],
        ];

        $result = ProviderConfig::merge($c1, $c2);
        $this->assertSame(['L1', null], $result['listeners']);
    }

    public function testProviderConfigLoadProviders()
    {
        $config = json_decode(file_get_contents(BASE_PATH . '/composer.json'), true);

        $providers = $config['extra']['hyperf']['config'];

        $res = ProviderConfig::loadProviders($providers);

        $dependencies = $res['dependencies'];
        $commands = $res['commands'];
        $scanPaths = $res['annotations']['scan']['paths'];
        $publish = $res['publish'];
        $listeners = $res['listeners'];

        $this->assertFalse(Arr::isAssoc($commands));
        $this->assertFalse(Arr::isAssoc($scanPaths));
        $this->assertFalse(Arr::isAssoc($listeners));
        $this->assertFalse(Arr::isAssoc($publish));
        $this->assertTrue(Arr::isAssoc($dependencies));
    }

    public function testProviderConfigLoadProvidersHasCallable()
    {
        $res = ProviderConfig::loadProviders([
            FooConfigProvider::class,
        ]);

        foreach ($res['dependencies'] as $dependency) {
            $this->assertTrue(is_string($dependency) || is_callable($dependency));
        }
    }
}
