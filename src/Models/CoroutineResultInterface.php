<?php

namespace Poller\Models;

interface CoroutineResultInterface
{
    public function getIp():string;

    public function toArray():array;
}
