<?php

namespace HuangYi\Swoole\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Swoole Table Facade.
 *
 * @method static void join(string $path, int $socketId)
 * @method static void broadcast(string $path, \HuangYi\Swoole\Contracts\MessageContract $message, array|int $excepts = null)
 * @method static void emit(int $socketId, \HuangYi\Swoole\Contracts\MessageContract $message)
 * @method static \Swoole\Server getServer()
 * @method static string getPath(int $socketId)
 * @method static array getClients(string $path)
 * @method static void flushAll()
 * @method static void flush(string $path)
 *
 * @see \HuangYi\Swoole\Websocket\NamespaceManager
 */
class WebsocketNamespace extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'swoole.websocket.namespace';
    }
}
