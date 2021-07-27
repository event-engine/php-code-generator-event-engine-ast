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

    public static function forVertex(\Throwable $previousException, string $name, string $type): self
    {
        $message = \sprintf(
            'Could not parse metadata for "%s" (%s) due: %s',
            $name,
            $type,
            $previousException->getMessage()
        );

        return new self($message, $previousException->getCode(), $previousException);
    }
}
