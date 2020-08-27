<?php

namespace Poller\Models;

interface CoroutineResultInterface
{
    public function getID():int;

    public function toArray():array;
}
