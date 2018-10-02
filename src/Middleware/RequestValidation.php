<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest\Middleware;

use N1215\PSR15FormRequest\Handlers\ValidatableHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class RequestValidation
 * @package App\Middleware
 */
class RequestValidation implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * コンストラクタ
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$handler instanceof ValidatableHandlerInterface) {
            return $handler->handle($request);
        }

        $formRequest = $handler->getFormRequest();
        if (!$formRequest->authorize($request)) {
            return $this->responseFactory
                ->createResponse(401)
                ->withBody($this->streamFactory->createStream(\json_encode([
                    'message' => 'unauthorized.'
                ])))
                ->withHeader('content-type', 'application/json');
        }

        if ($formRequest->fails($request)) {
            return $this->responseFactory
                ->createResponse(422, '')

                ->withBody($this->streamFactory->createStream(\json_encode([
                    'message' => 'validation failed.',
                    'errors' => $formRequest->errors()
                ])))
                ->withHeader('content-type', 'application/json');
        }

        return $handler->handle($request);
    }
}
