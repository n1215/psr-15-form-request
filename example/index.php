<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
use Illuminate\Validation\Factory;
use N1215\PSR15FormRequest\FormRequest;
use N1215\PSR15FormRequest\FormRequestInterface;
use N1215\PSR15FormRequest\Handlers\ValidatableHandlerInterface;
use N1215\PSR15FormRequest\Middleware\RequestValidation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

// 1. implement FormRequestInterface. you can use abstract class for convenience.
class ExampleFormRequest extends FormRequest
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

    protected function messages(): array
    {
        return [];
    }

    protected function attributes(): array
    {
        return [
            'id' => 'ID',
        ];
    }
}

// 2. implement ValidatableHandlerInterface. this interface extends PSR-15 RequestHandlerInterface and returns FormRequest.
class ExampleRequestHandler implements ValidatableHandlerInterface
{
    /**
     * @var ExampleFormRequest
     */
    private $formRequest;

    /**
     * @param ExampleFormRequest $formRequest
     */
    public function __construct(ExampleFormRequest $formRequest)
    {
        $this->formRequest = $formRequest;
    }

    public function getFormRequest(): FormRequestInterface
    {
        return $this->formRequest;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new \Zend\Diactoros\Response\JsonResponse(['message' => 'handled.']);
    }
}

// 3. instantiate your validatable handler.
$langDirPath = __DIR__ . '/../resources/lang';
$validationFactory = new Factory(new Translator(new FileLoader(new Filesystem(), $langDirPath), 'en'));
$formRequest = new ExampleFormRequest($validationFactory);
$handler = new ExampleRequestHandler($formRequest);

// 4. instantiate RequestValidation middleware.
$middleware = new RequestValidation(new ResponseFactory, new StreamFactory());

// 5. handle request and emit response.
$request = ServerRequestFactory::fromGlobals();
$response = $middleware->process($request, $handler);
(new SapiEmitter())->emit($response);
