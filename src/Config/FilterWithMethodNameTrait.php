<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\AggregateStateWithMethod;

trait FilterWithMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterWithMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterWithMethodName(): callable
    {
        if (null === $this->filterWithMethodName) {
            $this->filterWithMethodName = new AggregateStateWithMethod($this->getFilterConstValue());
        }

        return $this->filterWithMethodName;
    }

    public function setFilterWithMethodName(callable $filterWithMethodName): void
    {
        $this->filterWithMethodName = $filterWithMethodName;
    }
}
