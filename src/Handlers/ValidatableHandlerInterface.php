<?php
declare(strict_types=1);

namespace N1215\PSR15FormRequest\Handlers;

use N1215\PSR15FormRequest\FormRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Interface ValidatableHandlerInterface
 * @package N1215\PSR15FormRequest\Handlers
 */
interface ValidatableHandlerInterface extends RequestHandlerInterface
{
    public function getFormRequest(): FormRequestInterface;
}
