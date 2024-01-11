<?php
declare(strict_types=1);

namespace Exewen\Di\Contract;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function get($id);

    public function has($id);

    public function bind(string $abstract, $concrete = null);

    public function singleton(string $abstract, $concrete = null);

}
