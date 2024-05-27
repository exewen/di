<?php
declare(strict_types=1);

namespace ExewenTest\Di;

use Exewen\Di\Contract\ContainerInterface;

class DemoClass
{
//    public static int $constructCount = 0;
    public static $constructCount = 0;

    public function __construct(ContainerInterface $container)
    {
        self::$constructCount++;
    }

    public function getConstructCount(): int
    {
        return self::$constructCount;
    }

}