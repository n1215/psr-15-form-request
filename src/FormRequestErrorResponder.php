<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class FormRequestErrorResponder
 * @package N1215\PSR15FormRequest\Responders
 */
class FormRequestErrorResponder
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
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @return ResponseInterface
     */
    public function unauthorized(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(401)
            ->withBody($this->streamFactory->createStream(\json_encode([
                'message' => 'unauthorized.'
            ])))
            ->withHeader('content-type', 'application/json');
    }

    /**
     * @param array $errors
     * @return ResponseInterface
     */
    public function validationFailed(array $errors): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(422)

            ->withBody($this->streamFactory->createStream(\json_encode([
                'message' => 'validation failed.',
                'errors' => $errors
            ])))
            ->withHeader('content-type', 'application/json');
    }
}
