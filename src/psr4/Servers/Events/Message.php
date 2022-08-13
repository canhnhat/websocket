<?php

namespace NTC\WS\Servers\Events;

use Swoole\WebSocket\Server;

class Message extends Base
{
    /**
     * @param string $serverString
     * @param mixed $fd
     * @param mixed $data
     */
    public function __construct(
        public Server $server,
        public mixed $fd,
        public mixed $data
    ) {
        parent::__construct($server);
        dump("EVENT ". get_class($this));
    }
}
