<?php
declare(strict_types=1);

namespace Exewen\Di\Contract;

/**
 * 服务提供接口类
 */
interface ServiceProviderInterface
{
    /**
     * 服务注册
     * @return mixed
     */
    public function register();

}