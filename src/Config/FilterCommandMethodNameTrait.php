<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterCommandMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterCommandMethodName;

    public function getFilterCommandMethodName(): callable
    {
        if (null === $this->filterCommandMethodName) {
            $this->filterCommandMethodName = new Noop();
        }

        return $this->filterCommandMethodName;
    }

    public function setFilterCommandMethodName(callable $filterCommandMethodName): void
    {
        $this->filterCommandMethodName = $filterCommandMethodName;
    }
}
