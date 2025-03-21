<?php
declare(strict_types=1);

namespace ExewenTest\Di;

use Exewen\Di\Context\ApplicationContext;
use Exewen\Di\Contract\ContainerInterface;
use ExewenTest\Di\DemoClass\DemoClass;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();
        !defined('BASE_PATH_PKG') && define('BASE_PATH_PKG', dirname(__DIR__, 1));
    }

    /**
     * 单例-实例
     * @return void
     */
    public function testInstance()
    {
        $app      = ApplicationContext::getContainer();
        $concrete = new DemoClass($app);
        $app->instance(DemoClass::class, $concrete);

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);

        $instance->addConstructCount();

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(2, $check);
    }

    /**
     * 单例-类
     * @return void
     */
    public function testSingleton()
    {
        $app = ApplicationContext::getContainer();
        $app->singleton(DemoClass::class, DemoClass::class);

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);

        $instance->addConstructCount();

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(2, $check);
    }

    /**
     * 非单例-闭包
     * @return void
     */
    public function testBindClosure()
    {
        $app = ApplicationContext::getContainer();
        $app->bind(DemoClass::class, function (ContainerInterface $container) {
            return new DemoClass($container);
        });

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);
        $instance->addConstructCount();
        $check = $instance->getConstructCount();
        $this->assertEquals(2, $check);

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);
    }

    /**
     * 非单例-类
     * @return void
     */
    public function testBindClass()
    {
        $app = ApplicationContext::getContainer();
        $app->bind(DemoClass::class, DemoClass::class);

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);
        $instance->addConstructCount();
        $check = $instance->getConstructCount();
        $this->assertEquals(2, $check);

        /** @var DemoClass $instance */
        $instance = $app->get(DemoClass::class);
        $check    = $instance->getConstructCount();
        $this->assertEquals(1, $check);
    }


}