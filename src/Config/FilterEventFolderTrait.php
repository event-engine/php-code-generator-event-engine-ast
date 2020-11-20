<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

trait FilterEventFolderTrait
{
    /**
     * @var callable
     **/
    private $filterEventFolder;

    public function getFilterEventFolder(): ?callable
    {
        return $this->filterEventFolder;
    }

    public function setFilterEventFolder(?callable $filterEventFolder): void
    {
        $this->filterEventFolder = $filterEventFolder;
    }
}
