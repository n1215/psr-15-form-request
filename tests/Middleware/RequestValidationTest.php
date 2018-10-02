<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest\Middleware;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use N1215\PSR15FormRequest\FormRequest;
use N1215\PSR15FormRequest\FormRequestInterface;
use N1215\PSR15FormRequest\Handlers\ValidatableHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;

class RequestValidationTest extends TestCase
{
    /**
     * @var Factory
     */
    private $validationFactory;

    /**
     * @var RequestValidation
     */
    private $middleware;

    /**
     * @var ServerRequestFactoryInterface
     */
    private $requestFactory;

    public function setUp()
    {
        parent::setUp();
        $langDirPath = __DIR__ . '/../../resources/lang';
        $this->validationFactory = new Factory(new Translator(new FileLoader(new Filesystem(), $langDirPath), 'en'));
        $this->middleware = new RequestValidation(new ResponseFactory, new StreamFactory());
        $this->requestFactory = new ServerRequestFactory();
    }

    public function test_process_returns_unauthorized_status_when_authorization_failed(): void
    {
        $formRequest = new UnauthorizeFormRequest($this->validationFactory);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory->createServerRequest('GET', 'http://example.com/example');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"unauthorized."}', $response->getBody()->__toString());
    }

    public function test_process_returns_unprocesable_entity_status_when_validation_failed(): void
    {
        $formRequest = new ValidationFormRequest($this->validationFactory);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory
            ->createServerRequest('GET', 'http://example.com/example');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"validation failed.","errors":{"id":["The ID field is required."]}}', $response->getBody()->__toString());
    }

    public function test_process_returns_original_response_when_validation_success(): void
    {
        $formRequest = new ValidationFormRequest($this->validationFactory);
        $handler = new SampleHandler($formRequest);
        $request = $this->requestFactory
            ->createServerRequest('GET', 'http://example.com/example')
            ->withQueryParams(['id' => 1]);

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"message":"handled."}', $response->getBody()->__toString());
    }

}

class SampleHandler implements ValidatableHandlerInterface
{
    private $formRequest;

    public function __construct(FormRequestInterface $formRequest)
    {
        $this->formRequest = $formRequest;
    }

    public function getFormRequest(): FormRequestInterface
    {
        return $this->formRequest;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
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