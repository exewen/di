<?php
declare(strict_types=1);

namespace ExewenTest\Di;

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

    /**
     * 绑定单例 instance
     * @return void
     */
    public function testInstance()
    {
        $app = new Container();
        $concrete = new DemoClass($app);
        $app->instance(DemoClass::class, $concrete);

        $app->get(DemoClass::class)->getConstructCount();
        $count = $app->get(DemoClass::class)->getConstructCount();
        DemoClass::$constructCount = 0;
        $this->assertEquals(1, $count);
    }

    /**
     * 绑定单例 singleton
     * @return void
     */
    public function testSingleton()
    {
        $app = new Container();
        $app->singleton(DemoClass::class, DemoClass::class);

        $app->get(DemoClass::class)->getConstructCount();
        $count = $app->get(DemoClass::class)->getConstructCount();
        DemoClass::$constructCount = 0;
        $this->assertEquals(1, $count);
    }

    /**
     * 绑定非单例 closure
     * @return void
     */
    public function testBindClosure()
    {
        $app = new Container();
        $app->bind(DemoClass::class, function (ContainerInterface $container) {
            return new DemoClass($container);
        });

        $app->get(DemoClass::class)->getConstructCount();
        $count = $app->get(DemoClass::class)->getConstructCount();
        DemoClass::$constructCount = 0;
        $this->assertEquals(2, $count);
    }

    /**
     * 绑定非单例 class
     * @return void
     */
    public function testBindClass()
    {
        $app = new Container();
        $app->bind(DemoClass::class, DemoClass::class);

        $app->get(DemoClass::class)->getConstructCount();
        $count = $app->get(DemoClass::class)->getConstructCount();
        DemoClass::$constructCount = 0;
        $this->assertEquals(2, $count);
    }

}