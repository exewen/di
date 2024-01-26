<?php
declare(strict_types=1);

namespace Exewen\Di;

use Closure;
use Exewen\Config\Config;
use Exewen\Config\ConfigProvider;
use Exewen\Config\Contract\ConfigInterface;
use Exewen\Di\Contract\ContainerInterface;
use Exewen\Di\Contract\ServiceProviderInterface;
use Exewen\Di\Exception\ContainerException;
use Exewen\Di\Exception\InvalidConfigException;

class Container implements ContainerInterface
{
    protected static ContainerInterface $instance;

    protected array $instances = [];
    protected array $bindings = [];

    protected array $providers = [
        ConfigProvider::class
    ];

    protected array $dependencies = [
        ConfigInterface::class => Config::class,
    ];

    /**
     * 初始化DI （配置初始化+服务注册）
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct()
    {
        $this->initialConfiguration();
        self::setInstance($this);
    }

    /**
     * 绑定实例或者闭包
     * @param string $abstract
     * @param $concrete
     * @return void
     */
    public function bind(string $abstract, $concrete = null): void
    {
        $abstract = $this->getFinalAbstract($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // 非闭包进行处理
        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new InvalidConfigException(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }
            // 绑定的实例是通过绑定的闭包实现单例
            $concrete = $this->getClosure($abstract);
        }

        // $concrete 1、闭包 2、空（字符串 $concrete=$abstract）3、实例
        $this->make($abstract, $concrete);
        $this->set($abstract, $concrete, false);
    }

    /**
     * 绑定单例
     * @param string $abstract
     * @param $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $abstract = $this->getFinalAbstract($abstract);

        $concrete = $this->make($abstract, $concrete, true);
        $this->set($abstract, $concrete, true);
    }

    protected function make(string $abstract, $concrete = null)
    {
        // 是否存在单例
        if ($this->abstractShared($abstract)) {
            return $this->get($abstract);
        }

        if ($concrete == null || is_string($concrete)) {
            // 依赖注入
            $concrete = $this->getMakeInstance($abstract);
        }
        return $concrete;
    }

    public function get($id)
    {
        $abstract = $this->getFinalAbstract($id);

        if (!$this->has($abstract)) {
            throw new ContainerException(sprintf("Container not find: %s", $abstract));
        }

        $instances = $this->instances[$abstract];
        if ($instances instanceof Closure) {
            $instances = $instances($this);
        }

        return $instances;
    }

    public function has($id): bool
    {
        $abstract = $this->getFinalAbstract($id);

        return isset($this->instances[$abstract]);
    }


    /**
     * 转化闭包
     * @param $abstract
     * @return Closure
     */
    protected function getClosure($abstract): Closure
    {
        return function () use ($abstract) {
            return $this->getMakeInstance($abstract);
        };
    }

    public static function setInstance(ContainerInterface $container = null): ?ContainerInterface
    {
        return static::$instance = $container;
    }

    public static function getInstance(): ContainerInterface
    {
        return static::$instance;
    }

    /**
     * 依赖注入
     * @param $abstract
     * @return int|mixed|object|null
     */
    private function getMakeInstance($abstract)
    {
        // 接口类映射
        $abstract = $this->convertDependencies($abstract);

        $reflector = new \ReflectionClass($abstract);
        // 获取构造方法
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $abstract();
        }
        // 获取构造方法参数
        $dependencies = $constructor->getParameters();
        if (!$dependencies) {
            return new $abstract();
        }

        // 依赖注入
        $p = [];
        foreach ($dependencies as $dependency) {
            if (!is_null($dependency->getClass())) {
                $p[] = $this->make($dependency->getClass()->name);
            }
        }
        return $reflector->newInstanceArgs($p);
    }

    /**
     * 转换接口到实现类绑定 获反转
     * @param $abstract
     * @return int|mixed|string
     */
    private function convertDependencies($abstract)
    {
        return $this->dependencies[$abstract] ?? $abstract;
    }

    /**
     * 服务提供者
     * @return void
     */
    private function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $prov = new $provider($this);
            if ($prov instanceof ServiceProviderInterface || $prov instanceof ConfigProvider) {
                $prov->register();
            }
        }
    }

    /**
     * 配置初始化+服务注册
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function initialConfiguration()
    {
        // 1、服务注册：核心服务（ConfigProvider）
        $this->registerProviders();

        // 2、配置接口实例映射
        if ($dependencies = $this->get(ConfigInterface::class)->get('dependencies')) {
            $this->setDependencies($dependencies);
        }

        // 3、服务注册：非核心
        if ($providers = $this->get(ConfigInterface::class)->get('providers')) {
            $this->setProviders($providers);
        }

    }

    private function set(string $abstract, $concrete, $shared)
    {
        $this->bindings[$abstract] = $shared;
        return $this->instances[$abstract] = $concrete;
    }

    private function getFinalAbstract($id)
    {
        return $this->convertDependencies($id);
    }

    private function abstractShared(string $abstract)
    {
        $abstract = $this->getFinalAbstract($abstract);

        return $this->bindings[$abstract] ?? false;
    }

    public function setProviders(array $providers): void
    {
        $this->providers = array_merge($this->providers, $providers);
        $this->registerProviders();
    }

    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = array_merge($this->dependencies, $dependencies);
    }


}
