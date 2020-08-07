<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Filter;

abstract class AbstractFilter implements Filter
{
    /**
     * @var callable
     **/
    protected $filter;

    public function __construct(callable $filter = null)
    {
        $this->filter = $filter ?? new Noop();
    }
}
