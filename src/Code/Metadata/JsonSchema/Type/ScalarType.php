<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

abstract class ScalarType extends Type implements SupportsRequired
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @var array
     */
    protected $enum;

    /**
     * @var mixed
     */
    protected $const;

    /**
     * @var string
     */
    protected $default;

    /**
     * @var bool
     */
    protected $required = false;

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getEnum(): ?array
    {
        return $this->enum;
    }

    public function setEnum(array $enum): self
    {
        $this->enum = $enum;

        return $this;
    }

    public function getConst()
    {
        return $this->const;
    }

    public function setConst($const): self
    {
        $this->const = $const;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     * @return self
     */
    public function setDefault($default): self
    {
        $this->default = $default;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): SupportsRequired
    {
        $this->required = $required;

        return $this;
    }
}
