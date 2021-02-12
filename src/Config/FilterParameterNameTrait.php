<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use OpenCodeModeling\Filter\Filter\Noop;

trait FilterParameterNameTrait
{
    /**
     * @var callable
     **/
    private $filterParameterMethodName;

    public function getFilterParameterName(): callable
    {
        if (null === $this->filterParameterMethodName) {
            $this->filterParameterMethodName = new Noop();
        }

        return $this->filterParameterMethodName;
    }

    public function setFilterParameterName(callable $filterCommandMethodName): void
    {
        $this->filterParameterMethodName = $filterCommandMethodName;
    }
}
