<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\LowerCaseFirst;

trait FilterParameterNameTrait
{
    /**
     * @var callable
     **/
    private $filterParameterMethodName;

    abstract public function getFilterConstValue(): callable;

    public function getFilterParameterName(): callable
    {
        if (null === $this->filterParameterMethodName) {
            $this->filterParameterMethodName = new LowerCaseFirst($this->getFilterConstValue());
        }

        return $this->filterParameterMethodName;
    }

    public function setFilterParameterName(callable $filterCommandMethodName): void
    {
        $this->filterParameterMethodName = $filterCommandMethodName;
    }
}
