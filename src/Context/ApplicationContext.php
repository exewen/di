<?php

namespace Exewen\Di\Context;

use Exewen\Config\ConfigProvider;
use Exewen\Config\Contract\ConfigInterface;
use Exewen\Di\Container;
use Exewen\Di\Contract\ContainerInterface;

class ApplicationContext
{
//    protected static ?ContainerInterface $container = null;
    protected static $container = null;

    /**
     * 获取容器
     * @return ContainerInterface
     */
    public static function getContainer(): ContainerInterface
    {
        if (!ApplicationContext::hasContainer()) {
            $container = new Container();
            // 1、服务注册：ConfigProvider
            $container->setProviders([ConfigProvider::class]);
            // 2、根据配置设置 dependencies
            if ($dependencies = $container->get(ConfigInterface::class)->get('dependencies')) {
                $container->setDependencies($dependencies);
            }
            // 3、根据配置注册 providers
            if ($providers = $container->get(ConfigInterface::class)->get('providers')) {
                $container->setProviders($providers);
            }
            self::setContainer($container);
        }
        return self::$container;
    }

    public static function hasContainer(): bool
    {
        return isset(self::$container);
    }

    public static function setContainer(ContainerInterface $container): ContainerInterface
    {
        self::$container = $container;
        return $container;
    }
}