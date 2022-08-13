<?php

namespace NTC\WS;

use NTC\WS\Servers\Events\Open;
use Illuminate\Support\Str;
use NTC\WS\Servers\Events\Close;
use NTC\WS\Servers\Events\Message;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WSServer;

class Server
{
    /**
     * @var \Swoole\WebSocket\Server $ws
     */
    private WSServer $ws;

    /**
     * @param string $host
     * @param string $port
     */
    public function __construct(string $host, int $port)
    {
        $this->initWebsocket($host, $port);
    }


    /**
     * @return void
     */
    public function start() : void
    {
        $this->ws->start();
    }

    /**
     * @param string $host
     * @param string $port
     * @return void
     */
    private function initWebsocket(string $host, int $port) : void
    {
        $this->ws = new WSServer($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->ws->set(
            [
                'log_file' => storage_path('logs/swoole.log')
            ]
        );
        $this->ws->on("Start", fn (WSServer $server) => $this->wsOnStart($server));
        $this->ws->on("Close", fn (WSServer $server, mixed $fd) => $this->wsOnClose($server, $fd));
        $this->ws->on("Open", fn (WSServer $server, Request $request) => $this->wsOnOpen($server, $request));
        $this->ws->on(
            "Handshake",
            fn (Request $request, Response $response) => $this->wsOnHandshake($request, $response)
        );
        $this->ws->on("Message", fn (WSServer $server, Frame $frame) => $this->wsOnMessage($server, $frame));
        $this->ws->on("Disconnect", fn (WSServer $server, mixed $fd) => $this->wsOnDisconnect($server, $fd));
    }

    private function wsOnStart(WSServer $server)
    {
        dump('wsOnStart');
    }

    /**
     * @return bool
     */
    private function wsOnHandshake(Request $request, Response $response) : bool
    {
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';

        if (16 !== strlen(base64_decode($secWebSocketKey))
            || 0 === preg_match($patten, $secWebSocketKey)
            || !($apiKey = $request->get['apiKey'] ?? null)
            || $apiKey !== 'abcXyz'
        ) {
            $response->end();
            return false;
        }

        $uuid = strtoupper(Str::uuid()->toString());
        $uuid = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $key = base64_encode(sha1($request->header['sec-websocket-key'] . $uuid, true));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        $response->status(101);
        $response->end();
        return true;
    }


    private function wsOnClose(WSServer $server, mixed $fd)
    {
        $this->dispathEvent(Close::class, $server, $fd);
    }

    private function wsOnDisconnect(WSServer $server, mixed $fd)
    {
    }

    private function wsOnOpen(WSServer $server, Request $request)
    {
        $this->dispathEvent(Open::class, $server, $request->fd);
    }

    private function wsOnMessage(WSServer $server, Frame $frame)
    {
        $this->dispathEvent(Message::class, $server, $frame->fd, $frame->data);
    }

    private function wsOnRequest(Request $server, Response $response)
    {
        // foreach ($this->ws->connections as $fd) {
        //     if ($server->isEstablished($fd)) {
        //         $server->push($fd, $request->get['message']);
        //     }
        // }
    }

    /**
     * @return void
     */
    private function dispathEvent(string $eventClass, WSServer $server, ...$args) : void
    {
        $eventClass::dispatch($server, ...$args);
    }
}
