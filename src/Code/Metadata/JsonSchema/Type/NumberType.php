<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class NumberType extends ScalarType
{
    /**
     * @var float
     */
    protected $minimum;

    /**
     * @var float
     */
    protected $maximum;

    /**
     * @var bool
     */
    protected $exclusiveMinimum;

    /**
     * @var bool
     */
    protected $exclusiveMaximum;

    /**
     * @var float
     */
    protected $multipleOf;

    public function getMinimum(): float
    {
        return $this->minimum;
    }

    public function setMinimum(?float $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

    public function getMaximum(): float
    {
        return $this->maximum;
    }

    public function setMaximum(?float $maximum): self
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function getExclusiveMinimum(): ?bool
    {
        return $this->exclusiveMinimum;
    }

    public function setExclusiveMinimum(bool $exclusiveMinimum): self
    {
        $copy = clone $this;
        $copy->exclusiveMinimum = $exclusiveMinimum;

        return $copy;
    }

    public function getExclusiveMaximum(): ?bool
    {
        return $this->exclusiveMaximum;
    }

    public function setExclusiveMaximum(bool $exclusiveMaximum): self
    {
        $this->exclusiveMaximum = $exclusiveMaximum;

        return $this;
    }

    public function getMultipleOf(): float
    {
        return $this->multipleOf;
    }

    public function setMultipleOf(?float $multipleOf): self
    {
        $this->multipleOf = $multipleOf;

        return $this;
    }

    public function getType(): string
    {
        return self::TYPE_NUMBER;
    }
}
