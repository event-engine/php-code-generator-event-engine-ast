<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

trait FilterValueObjectFolderTrait
{
    /**
     * @var callable
     **/
    private $filterValueObjectFolder;

    public function getFilterValueObjectFolder(): ?callable
    {
        return $this->filterValueObjectFolder;
    }

    public function setFilterValueObjectFolder(?callable $filterValueObjectFolder): void
    {
        $this->filterValueObjectFolder = $filterValueObjectFolder;
    }
}
