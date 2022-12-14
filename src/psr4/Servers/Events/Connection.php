<?php

namespace NTC\WS\Servers\Events;

use Swoole\WebSocket\Server;

class Connection extends Base
{
    /**
     * @param \Swoole\WebSocket\Server $server
     * @param mixed $fd
     */
    public function __construct(
        public Server $server,
        public mixed $fd
    ) {
        parent::__construct($server);
    }
}
