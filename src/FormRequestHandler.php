<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class FormRequestHandler
 * @package N1215\PSR15FormRequest\Handlers
 */
abstract class FormRequestHandler implements RequestHandlerInterface
{
    /**
     * @var \N1215\PSR15FormRequest\FormRequest
     */
    private $middleware;

    /**
     * @param FormRequestMiddlewareInterface $middleware
     */
    public function __construct(FormRequestMiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $callable = function (ServerRequestInterface $request): ResponseInterface {
            return $this->innerHandle($request);
        };

        return $this->middleware->process($request, new class($callable) implements RequestHandlerInterface {
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return \call_user_func($this->callable, $request);
            }

        });
    }

    abstract protected function innerHandle(ServerRequestInterface $request): ResponseInterface;
}
