<?php

namespace NTC\WS\Servers\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Swoole\WebSocket\Server;

abstract class Base
{
    use Dispatchable;

    /**
     * @param \Swoole\WebSocket\Server $server
     */
    public function __construct(public Server $server)
    {
    }

    /**
     * @return string
     */
    public function getServerString() : string
    {
        return "ws://{$this->server->host}:{$this->server->port}";
    }
}
