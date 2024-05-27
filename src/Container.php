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

/**
 * 容器实现类
 */
class Container implements ContainerInterface
{
    /**
     * 全局容器
     * @var ContainerInterface
     */
//    protected static ContainerInterface $instance;
    protected static  $instance;

    /**
     * Di树
     * 实例名key优先
     *
     * @var array
     */
//    protected array $instances = [];
    protected  $instances = [];

    /**
     * 记录单例对象
     * @var array
     */
//    protected array $bindings = [];
    protected $bindings = [];

    /**
     * 服务提供者（服务注册上树）
     * @var array|string[]
     */
//    protected array $providers = [
    protected $providers = [
        ConfigProvider::class
    ];

    /**
     * 接口->实现映射
     * @var array|string[]
     */
//    protected array $dependencies = [
    protected $dependencies = [
        ContainerInterface::class => Container::class,
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
     * 注册绑定（单例）
     * @param string $abstract
     * @param $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $abstract = $this->getFinalAbstract($abstract);
        $concrete = $this->make($abstract, $concrete);
        $this->set($abstract, $concrete, true);
    }

    /**
     * 注册绑定（单例 直接绑定 无依赖注入）
     * @param string $abstract
     * @param $concrete
     * @return void
     */
    public function instance(string $abstract, $concrete = null): void
    {
        $this->set($abstract, $concrete, true);
    }

    /**
     * 绑定实例或者闭包
     *
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

        // 对$concrete=string类，进行闭包转换
        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new InvalidConfigException(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }
            // 绑定的实例，通过绑定的闭包实现非单例
            $concrete = $this->getClosure($abstract);
        }

        // $concrete 最终为闭包
        $this->set($abstract, $concrete, false);
    }


    /**
     * 解析实例化对象
     * @param string $abstract
     * @param $concrete
     * @return int|mixed|object|null
     */
    protected function make(string $abstract, $concrete = null)
    {
        // 存在单例 不再解析
        if ($this->abstractShared($abstract)) {
            return $this->get($abstract);
        }

        if ($concrete == null || is_string($concrete)) {
            // 依赖注入
            $concrete = $this->build($abstract);
        }
        return $concrete;
    }

    /**
     * 获取容器实例
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        $abstract = $this->getFinalAbstract($id);

        if (!$this->has($abstract)) {
            throw new ContainerException(sprintf("Container not find: %s", $abstract));
        }

        $instances = $this->instances[$abstract];
        // 闭包，导入容器
        if ($instances instanceof Closure) {
            $instances = $instances($this);
        }

        return $instances;
    }

    /**
     * 是否存在容器
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->instances[$this->getFinalAbstract($id)]);
    }


    /**
     * 转化闭包
     * @param $abstract
     * @return Closure
     */
    protected function getClosure($abstract): Closure
    {
        return function (ContainerInterface $container) use ($abstract) {
            return $this->build($abstract);
        };
    }

    /**
     * 设置全局容器
     * @param ContainerInterface|null $container
     * @return ContainerInterface|null
     */
    public static function setInstance(ContainerInterface $container = null): ?ContainerInterface
    {
        return static::$instance = $container;
    }

    /**
     * 获取全局容器
     * @return ContainerInterface
     */
    public static function getInstance(): ContainerInterface
    {
        return static::$instance;
    }

    /**
     * 依赖注入构建
     * @param string $abstract
     * @return int|mixed|object|null
     */
    private function build(string $abstract)
    {
        $abstract = $this->getFinalAbstract($abstract);
        // 获取反射类
        $reflector = new \ReflectionClass($abstract);
        // 无构造方法活 无构造方法参数，直接实例化
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $abstract();
        }
        $dependencies = $constructor->getParameters();
        if (!$dependencies) {
            return new $abstract();
        }

        /** 依赖注入 */
        $p = [];
        foreach ($dependencies as $dependency) {
            if (!is_null($dependency->getClass())) {
                $p[] = $this->make($dependency->getClass()->name);
            }
        }
        return $reflector->newInstanceArgs($p);
    }


    /**
     * 实现服务注册
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

    /**
     * 实例注册
     * @param string $abstract
     * @param $concrete
     * @param bool $shared 是否单例
     * @return void
     */
    private function set(string $abstract, $concrete, bool $shared)
    {
        $this->bindings[$abstract] = $shared;
        $this->instances[$abstract] = $concrete;
    }

    /**
     * 获取最终实现类
     * @param $abstract
     * @return int|mixed|string
     */
    private function getFinalAbstract($abstract)
    {
        return $this->dependencies[$abstract] ?? $abstract;
    }

    /**
     * 是否存在单例
     * @param string $abstract
     * @return false|mixed
     */
    private function abstractShared(string $abstract)
    {
        $abstract = $this->getFinalAbstract($abstract);

        return $this->bindings[$abstract] ?? false;
    }

    /**
     * 实现服务注册+写入providers
     * @param array $providers
     * @return void
     */
    public function setProviders(array $providers): void
    {
        $this->providers = array_merge($this->providers, $providers);
        $this->registerProviders();
    }

    /**
     * 写入接口->实现映射
     * @param array $dependencies
     * @return void
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = array_merge($this->dependencies, $dependencies);
    }

    private function namespace_replace($provider)
    {
        // 命名空间兼容
        if (substr($provider, 0, 1) !== '\\') {
            $provider = '\\' . $provider;
        }
        return $provider;
    }


}
