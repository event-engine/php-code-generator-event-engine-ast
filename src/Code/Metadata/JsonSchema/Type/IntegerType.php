<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class IntegerType extends ScalarType
{
    /**
     * @var int
     */
    protected $minimum;

    /**
     * @var int
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
     * @var int
     */
    protected $multipleOf;

    public function getMinimum(): int
    {
        return $this->minimum;
    }

    public function setMinimum(?int $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

    public function getMaximum(): int
    {
        return $this->maximum;
    }

    public function setMaximum(?int $maximum): self
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
        $this->exclusiveMinimum = $exclusiveMinimum;

        return $this;
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

    public function getMultipleOf(): int
    {
        return $this->multipleOf;
    }

    public function setMultipleOf(?int $multipleOf): self
    {
        $this->multipleOf = $multipleOf;

        return $this;
    }

    public function getType(): string
    {
        return self::TYPE_NUMBER;
    }
}
