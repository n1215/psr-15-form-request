<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
use Illuminate\Validation\Factory;
use N1215\PSR15FormRequest\FormRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

// 1. implement FormRequestMiddlewareInterface. you can use abstract class for convenience.
class YourFormRequest extends FormRequest
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

// 2. extend FormRequestHandler for your FormRequest
class YourRequestHandler extends \N1215\PSR15FormRequest\FormRequestHandler
{
    /**
     * @param YourFormRequest $formRequest
     */
    public function __construct(YourFormRequest $formRequest)
    {
        parent::__construct($formRequest);
    }

    protected function innerHandle(ServerRequestInterface $request): ResponseInterface
    {
        return new \Zend\Diactoros\Response\JsonResponse(['message' => 'handled.']);
    }
}

// 3. instantiate your FormRequest middleware.
$langDirPath = __DIR__ . '/../resources/lang';
$validationFactory = new Factory(new Translator(new FileLoader(new Filesystem(), $langDirPath), 'en'));
$errorResponder = new \N1215\PSR15FormRequest\FormRequestErrorResponder(new ResponseFactory(), new StreamFactory());
$formRequest = new YourFormRequest($validationFactory, $errorResponder);

// 4. instantiate your FormRequestHandler.
$handler = new YourRequestHandler($formRequest);

// 5. handle request and emit response.
$request = ServerRequestFactory::fromGlobals();
$response = $handler->handle($request);
(new SapiEmitter())->emit($response);
