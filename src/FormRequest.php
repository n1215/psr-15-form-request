<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class FormRequest
 * @package App\Requests
 */
abstract class FormRequest implements FormRequestInterface
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
     * @param Factory $validatorFactory
     */
    public function __construct(Factory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
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

    private function makeValidator(ServerRequestInterface $request): Validator
    {
        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $inputs = \is_array($body) ? array_merge($query, $body) : $query;

        return $this->validatorFactory->make($inputs, $this->rules(), $this->messages(), $this->attributes());
    }

    public function authorize(ServerRequestInterface $request): bool
    {
        return false;
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function fails(ServerRequestInterface $request): bool
    {
        $this->validator = $this->makeValidator($request);
        return $this->validator->fails();
    }

    /**
     * @return array
     */
    public function errors(): array
    {
        if ($this->validator === null) {
            throw new \LogicException('execute FormRequest::fails() method before getting errors');
        }

        return $this->validator->errors()->toArray();
    }
}
