<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Interface FormRequestMiddlewareInterface
 * @package N1215\PSR15FormRequest
 */
interface FormRequestMiddlewareInterface extends FormRequestInterface, MiddlewareInterface
{
}
