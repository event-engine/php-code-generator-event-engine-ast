<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Exception;

final class ErrorParsingMetadata extends RuntimeException
{
    public static function withError(string $message, \Throwable $previousException): self
    {
        return new self($message, $previousException->getCode(), $previousException);
    }
}
