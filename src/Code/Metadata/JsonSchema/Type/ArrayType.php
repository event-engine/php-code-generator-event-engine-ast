<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class ArrayType extends Type
{
    /**
     * @var Type[]
     */
    protected $items = [];

    /**
     * @var Type[]
     */
    protected $contains = [];

    /**
     * @var integer
     */
    protected $minItems;

    /**
     * @var integer
     */
    protected $maxItems;

    /**
     * @var boolean
     */
    protected $uniqueItems;

    /**
     * @var Type|null
     */
    protected $additionalItems;

    /**
     * @var array<string, Type>
     */
    protected $definitions = [];

    public function definitions(): array
    {
        return $this->definitions;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(Type ...$items): self
    {
        $this->items = $items;

        return $this;
    }

    public function addItem(Type $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function setMinItems(int $minItems): self
    {
        $this->minItems = $minItems;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function setMaxItems(int $maxItems): self
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function isUniqueItems(): ?bool
    {
        return $this->uniqueItems;
    }

    public function setUniqueItems(bool $uniqueItems): self
    {
        $this->uniqueItems = $uniqueItems;

        return $this;
    }

    public function getUniqueItems(): bool
    {
        return $this->uniqueItems;
    }

    /**
     * @return Type[]
     */
    public function getContains(): array
    {
        return $this->contains;
    }

    public function getAdditionalItems(): ?Type
    {
        return $this->additionalItems;
    }

    public function getType(): string
    {
        return self::TYPE_ARRAY;
    }
}
