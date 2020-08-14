<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\StateName;

trait FilterAggregateStoreStateInTrait
{
    /**
     * @var callable
     **/
    private $filterAggregateStoreStateIn;

    public function getFilterAggregateStoreStateIn(): ?callable
    {
        if (null === $this->filterAggregateIdName) {
            $this->filterAggregateIdName = new StateName();
        }

        return $this->filterAggregateStoreStateIn;
    }

    public function setFilterAggregateStoreStateIn(?callable $filterAggregateStoreStateIn): void
    {
        $this->filterAggregateStoreStateIn = $filterAggregateStoreStateIn;
    }
}
