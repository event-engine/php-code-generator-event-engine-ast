<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Filter\Noop;

trait FilterConstNameTrait
{
    /**
     * @var callable
     **/
    private $filterConstName;

    public function getFilterConstName(): callable
    {
        if (null === $this->filterConstName) {
            $this->filterConstName = new Noop();
        }

        return $this->filterConstName;
    }

    public function setFilterConstName(callable $filterConstName): void
    {
        $this->filterConstName = $filterConstName;
    }
}
