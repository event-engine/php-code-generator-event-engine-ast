<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterMessageNameTrait
{
    /**
     * @var callable
     **/
    private $filterMessageName;

    public function getFilterMessageName(): callable
    {
        if (null === $this->filterMessageName) {
            $this->filterMessageName = new Noop();
        }

        return $this->filterMessageName;
    }

    public function setFilterMessageName(callable $filterClassName): void
    {
        $this->filterMessageName = $filterClassName;
    }
}
