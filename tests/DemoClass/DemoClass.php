<?php
declare(strict_types=1);

namespace ExewenTest\Di\DemoClass;

use Exewen\Di\Contract\ContainerInterface;

class DemoClass
{
    public $constructCount = 0;

    public function __construct(ContainerInterface $container)
    {
        $this->addConstructCount();
    }

    public function addConstructCount()
    {
        $this->constructCount++;
    }

    public function getConstructCount(): int
    {
        return $this->constructCount;
    }

}