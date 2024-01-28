<?php
declare(strict_types=1);

namespace Exewen\Di\Contract;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * 容器接口类
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * 获取实例
     * @param $id
     * @return mixed
     */
    public function get($id);

    /**
     * 是否存在实例
     * @param $id
     * @return mixed
     */
    public function has($id);

    /**
     * 绑定实例或者闭包
     * @param string $abstract
     * @param $concrete
     * @return mixed
     */
    public function bind(string $abstract, $concrete = null);

    /**
     * 绑定单例
     * @param string $abstract
     * @param $concrete
     * @return mixed
     */
    public function singleton(string $abstract, $concrete = null);

}
