<?php

namespace HuangYi\Swoole;

use HuangYi\Swoole\Transformers\RequestTransformer;
use HuangYi\Swoole\Transformers\ResponseTransformer;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Swoole\Http\Server as SwooleHttpServer;

class HttpServer extends Server
{
    /**
     * The http kernel.
     *
     * @var \Illuminate\Contracts\Http\Kernel
     */
    protected $httpKernel;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = ['request'];

    /**
     * Define swoole http server class.
     *
     * @return string
     */
    public function swooleServer()
    {
        return SwooleHttpServer::class;
    }

    /**
     * The listener of "workerStart" event.
     *
     * @param \Swoole\Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart($server, $workerId)
    {
        parent::onWorkerStart($server, $workerId);

        $this->clearCache();

        if (! $this->isLumen()) {
            $this->httpKernel = $this->container->make(Kernel::class);
            $this->httpKernel->bootstrap();
        }
    }

    /**
     * The listener of "request" event.
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return void
     */
    public function onRequest($request, $response)
    {
        $this->container['events']->fire('swoole.requesting', func_get_args());

        $this->container->instance('swoole.http.request', $request);

        $this->handleHttpRequest($request, $response);

        $this->container['events']->fire('swoole.requested', func_get_args());
    }

    /**
     * Handle http request.
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return void
     */
    protected function handleHttpRequest($request, $response)
    {
        $illuminateRequest = RequestTransformer::make($request)->toIlluminateRequest();

        if ($this->isLumen()) {
            $illuminateResponse = $this->container->handle($illuminateRequest);
        } else {
            $illuminateResponse = $this->httpKernel->handle($illuminateRequest);
        }

        ResponseTransformer::make($illuminateResponse)->send($response);

        if (! $this->isLumen()) {
            $this->httpKernel->terminate($illuminateRequest, $illuminateResponse);
        }

        $this->flushSession();

        $this->reset();
    }

    /**
     * Determine whether the framework is Lumen.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return $this->container instanceof LumenApplication;
    }

    /**
     * Flush session.
     *
     * @return void
     */
    protected function flushSession()
    {
        if ($this->container->resolved('session.store')) {
            $this->container['session.store']->flush();
            $this->container['session.store']->regenerate();

            $this->rebindAbstract(StartSession::class);
        }
    }

    /**
     * Reset instances and service providers.
     *
     * @return void
     */
    protected function reset()
    {
        $resets = $this->container['config']->get('swoole.resets', []);

        foreach ($resets as $abstract) {
            if ($abstract instanceof ServiceProvider) {
                $this->container->register($abstract, [], true);
            } else {
                $this->rebindAbstract($abstract);
            }
        }
    }

    /**
     * Rebind abstract.
     *
     * @param string $abstract
     * @return void
     */
    protected function rebindAbstract($abstract)
    {
        $abstract = $this->container->getAlias($abstract);
        $binding = $this->container->getBindings()[$abstract] ?: null;

        unset($this->container[$abstract]);

        if ($binding) {
            $this->container->bind($abstract, $binding['concrete'], $binding['shared']);
        }
    }

    /**
     * Get server name
     *
     * @return string
     */
    protected function getServerName()
    {
        return 'swoole-http-server';
    }
}
