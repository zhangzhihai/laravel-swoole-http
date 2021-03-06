<?php

namespace HuangYi\Swoole\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Message Route Facade.
 *
 * @method static void on(string $event, array|string|\Closure $action)
 *
 * @see \HuangYi\Swoole\Websocket\Message\Router
 */
class MessageRoute extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.websocket.message.router';
    }
}
