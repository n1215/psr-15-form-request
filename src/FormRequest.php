<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class FormRequest
 * @package App\Middleware
 */
abstract class FormRequest implements FormRequestMiddlewareInterface
{
    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var FormRequestErrorResponder
     */
    private $errorResponder;

    /**
     * @param Factory $validatorFactory
     * @param FormRequestErrorResponder $errorResponder
     */
    public function __construct(
        Factory $validatorFactory,
        FormRequestErrorResponder $errorResponder
    ) {
        $this->validatorFactory = $validatorFactory;
        $this->errorResponder = $errorResponder;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authorize($request)) {
            return $this->errorResponder->unauthorized();
        }

        $this->validator = $this->makeValidator($request);
        if ($this->fails($request)) {
            return $this->errorResponder->validationFailed($this->errors());
        }

        return $handler->handle($request);
    }

    /**
     * @inheritdoc
     */
    public function fails(ServerRequestInterface $request): bool
    {
        $this->validator = $this->makeValidator($request);
        return $this->validator->fails();
    }

    /**
     * @inheritdoc
     */
    public function errors(): array
    {
        if ($this->validator === null) {
            throw new \LogicException('execute FormRequest::fails() method before getting errors');
        }

        return $this->validator->errors()->toArray();
    }

    /**
     * @inheritdoc
     */
    public function authorize(ServerRequestInterface $request): bool
    {
        return false;
    }

    /**
     * validation rules
     * @return array
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * validation messages
     * @return array
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function attributes(): array
    {
        return [];
    }

    /**
     * @param ServerRequestInterface $request
     * @return Validator
     */
    private function makeValidator(ServerRequestInterface $request): Validator
    {
        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $inputs = \is_array($body) ? array_merge($query, $body) : $query;

        return $this->validatorFactory->make($inputs, $this->rules(), $this->messages(), $this->attributes());
    }
}
