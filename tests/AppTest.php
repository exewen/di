<?php
declare(strict_types=1);

namespace ExewenTest\Di;

use Exewen\Config\Contract\ConfigInterface;
use Exewen\Config\ConfigProvider;
use Exewen\Config\Config;
use Exewen\Di\Container;
use Exewen\Di\Contract\ContainerInterface;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();
        !defined('BASE_PATH_PKG') && define('BASE_PATH_PKG', dirname(__DIR__, 1));
    }

    public function testBindClosure()
    {
        $app = new Container();
        $app->bind(ConfigInterface::class, function (ContainerInterface $container) {
            $configProvider = new ConfigProvider($container);
            $config = $configProvider->getConfig();
            return new Config($config);
        });
        $config = $app->get(ConfigInterface::class);
        $dependencies = $config->get('dependencies');
//        $config=$app->get(ConfigInterface::class);
//        $dependencies = $config->get('dependencies');
//        $config=$app->get(ConfigInterface::class);
//        $dependencies = $config->get('dependencies');
        $this->assertNotEmpty($dependencies);
    }

    public function testSingleton()
    {
        #
        $app = new Container();
        $app->singleton(ConfigInterface::class);

        $config = $app->get(ConfigInterface::class);
        $dependencies = $config->get('dependencies');

        $this->assertNotEmpty($dependencies);
    }


}