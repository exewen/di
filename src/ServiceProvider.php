<?php
declare(strict_types=1);

namespace Exewen\Di;

use Exewen\Di\Contract\ContainerInterface;
use Exewen\Di\Contract\ServiceProviderInterface;

/**
 * 服务提供实现类
 */
abstract class ServiceProvider implements ServiceProviderInterface
{
//    protected ContainerInterface $container;
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

}