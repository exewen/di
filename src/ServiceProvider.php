<?php
declare(strict_types=1);

namespace Exewen\Di;

use Exewen\Di\Contract\ContainerInterface;
use Exewen\Di\Contract\ServiceProviderInterface;

abstract class ServiceProvider implements ServiceProviderInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

}