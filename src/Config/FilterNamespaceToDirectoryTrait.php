<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\Noop;

trait FilterNamespaceToDirectoryTrait
{
    /**
     * @var callable
     **/
    private $filterNamespaceToDirectory;

    public function getFilterNamespaceToDirectory(): callable
    {
        if (null === $this->filterNamespaceToDirectory) {
            $this->filterNamespaceToDirectory = new Noop();
        }

        return $this->filterNamespaceToDirectory;
    }

    public function setFilterNamespaceToDirectory(callable $filterDirectoryToNamespace): void
    {
        $this->filterNamespaceToDirectory = $filterDirectoryToNamespace;
    }
}
