<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface FormRequestInterface
 * @package N1215\PSR15FormRequest
 */
interface FormRequestInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function authorize(ServerRequestInterface $request): bool;

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function fails(ServerRequestInterface $request): bool;

    /**
     * @return array
     */
    public function errors(): array;
}
