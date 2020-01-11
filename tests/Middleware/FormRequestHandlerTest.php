<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest\Middleware;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use N1215\PSR15FormRequest\FormRequest;
use N1215\PSR15FormRequest\FormRequestErrorResponder;
use N1215\PSR15FormRequest\FormRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;

class FormRequestHandlerTest extends TestCase
{
    /**
     * @var Factory
     */
    private $validationFactory;

    /**
     * @var FormRequestErrorResponder
     */
    private $errorResponder;

    /**
     * @var ServerRequestFactoryInterface
     */
    private $requestFactory;


    public function setUp()
    {
        parent::setUp();
        $langDirPath = __DIR__ . '/../../resources/lang';
        $this->validationFactory = new Factory(new Translator(new FileLoader(new Filesystem(), $langDirPath), 'en'));
        $this->errorResponder = new FormRequestErrorResponder(new ResponseFactory, new StreamFactory());
        $this->requestFactory = new ServerRequestFactory();
    }

    public function test_handle_returns_unauthorized_status_when_authorization_failed(): void
    {
        $formRequest = new UnauthorizeFormRequest($this->validationFactory, $this->errorResponder);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory->createServerRequest('GET', 'http://example.com/example');

        $response = $handler->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"unauthorized."}', $response->getBody()->__toString());
    }

    public function test_handle_returns_unprocesable_entity_status_when_validation_failed(): void
    {
        $formRequest = new ValidationFormRequest($this->validationFactory, $this->errorResponder);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory
            ->createServerRequest('GET', 'http://example.com/example');

        $response = $handler->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"validation failed.","errors":{"id":["The ID field is required."]}}', $response->getBody()->__toString());
    }

    public function test_handle_returns_original_response_when_validation_success(): void
    {
        $formRequest = new ValidationFormRequest($this->validationFactory, $this->errorResponder);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory
            ->createServerRequest('GET', 'http://example.com/example')
            ->withQueryParams(['id' => 1]);

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"handled."}', $response->getBody()->__toString());
    }

}

class SampleHandler extends FormRequestHandler
{
    public function innerHandle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(['message' => 'handled.']);
    }
}

class ValidationFormRequest extends FormRequest
{

    public function authorize(ServerRequestInterface $request): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }

    protected function attributes(): array
    {
        return [
            'id' => 'ID',
        ];
    }
}

class UnauthorizeFormRequest extends FormRequest
{
}