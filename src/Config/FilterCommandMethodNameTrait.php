<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\AggregateBehaviourCommandMethod;

trait FilterCommandMethodNameTrait
{
    /**
     * @var callable
     **/
    private $filterCommandMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterCommandMethodName(): callable
    {
        if (null === $this->filterCommandMethodName) {
            $this->filterCommandMethodName = new AggregateBehaviourCommandMethod($this->getFilterConstValue());
        }

        return $this->filterCommandMethodName;
    }

    public function setFilterCommandMethodName(callable $filterCommandMethodName): void
    {
        $this->filterCommandMethodName = $filterCommandMethodName;
    }
}
