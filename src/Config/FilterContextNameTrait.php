<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterContextNameTrait
{
    /**
     * @var callable
     **/
    private $filterContextName;

    public function getFilterContextName(): callable
    {
        if (null === $this->filterContextName) {
            $this->filterContextName = new Noop();
        }

        return $this->filterContextName;
    }

    public function setFilterContextName(callable $filterClassName): void
    {
        $this->filterContextName = $filterClassName;
    }
}
