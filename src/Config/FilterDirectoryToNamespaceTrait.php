<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\Noop;

trait FilterDirectoryToNamespaceTrait
{
    /**
     * @var callable
     **/
    private $filterDirectoryToNamespace;

    public function getFilterDirectoryToNamespace(): callable
    {
        if (null === $this->filterDirectoryToNamespace) {
            $this->filterDirectoryToNamespace = new Noop();
        }

        return $this->filterDirectoryToNamespace;
    }

    public function setFilterDirectoryToNamespace(callable $filterDirectoryToNamespace): void
    {
        $this->filterDirectoryToNamespace = $filterDirectoryToNamespace;
    }
}
