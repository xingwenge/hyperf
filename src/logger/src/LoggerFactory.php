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

namespace Hyperf\Logger;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\Exception\InvalidConfigException;
use Hyperf\Utils\Arr;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var array
     */
    protected $loggers;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function make($name = 'hyperf', $group = 'default'): LoggerInterface
    {
        $config = $this->config->get('logger');
        if (! isset($config[$group])) {
            throw new InvalidConfigException(sprintf('Logger config[%s] is not defined.', $name));
        }

        $config = $config[$group];
        $handlers = $this->handlers($config);

        return make(Logger::class, [
            'name' => $name,
            'handlers' => $handlers,
        ]);
    }

    public function get($name = 'hyperf', $group = 'default'): LoggerInterface
    {
        if (isset($this->loggers[$name]) && $this->loggers[$name] instanceof Logger) {
            return $this->loggers[$name];
        }

        return $this->loggers[$name] = $this->make($name, $group);
    }

    protected function getDefaultFormatterConfig($config)
    {
        $formatterClass = Arr::get($config, 'formatter.class', LineFormatter::class);
        $formatterConstructor = Arr::get($config, 'formatter.constructor', []);

        return [
            'class' => $formatterClass,
            'constructor' => $formatterConstructor,
        ];
    }

    protected function getDefaultHandlerConfig($config)
    {
        $handlerClass = Arr::get($config, 'handler.class', StreamHandler::class);
        $handlerConstructor = Arr::get($config, 'handler.constructor', [
            'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
            'level' => Logger::DEBUG,
        ]);

        return [
            'class' => $handlerClass,
            'constructor' => $handlerConstructor,
        ];
    }

    protected function handlers(array $config): array
    {
        $handlerConfigs = $config['handlers'] ?? [[]];
        $handlers = [];
        $defaultHandlerConfig = $this->getDefaultHandlerConfig($config);
        $defaultFormatterConfig = $this->getDefaultFormatterConfig($config);
        foreach ($handlerConfigs as $value) {
            $class = $value['class'] ?? $defaultHandlerConfig['class'];
            $constructor = $value['constructor'] ?? $defaultHandlerConfig['constructor'];
            $formatterConfig = $value['formatter'] ?? $defaultFormatterConfig;

            $handlers[] = $this->handler($class, $constructor, $formatterConfig);
        }

        return $handlers;
    }

    protected function handler($class, $constructor, $formatterConfig): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = make($class, $constructor);

        $formatterClass = $formatterConfig['class'];
        $formatterConstructor = $formatterConfig['constructor'];

        /** @var FormatterInterface $formatter */
        $formatter = make($formatterClass, $formatterConstructor);

        $handler->setFormatter($formatter);

        return $handler;
    }
}
