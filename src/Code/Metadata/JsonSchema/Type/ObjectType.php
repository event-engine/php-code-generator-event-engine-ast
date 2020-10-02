<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class ObjectType extends Type
{
    /**
     * @var bool|Type
     */
    protected $additionalProperties;

    /**
     * @var array<string, string[]>
     */
    protected $dependencies = [];

    /**
     * @var array<string, Type>
     */
    protected $properties = [];

    /**
     * @var array<string>
     */
    protected $required = [];

    /**
     * @var array<string, Type>
     */
    protected $definitions = [];

    public function getType(): string
    {
        return self::TYPE_OBJECT;
    }

    /**
     * @return bool|Type
     */
    public function additionalProperties()
    {
        return $this->additionalProperties;
    }

    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return array<string, Type>
     */
    public function properties(): array
    {
        return $this->properties;
    }

    public function required(): array
    {
        return $this->required;
    }

    public function definitions(): array
    {
        return $this->definitions;
    }
}
