<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

final class ReferenceType extends Type
{
    /**
     * @var string
     */
    protected $ref;

    /**
     * @var Type|null
     */
    protected $resolvedType;

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setRef(string $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getType(): string
    {
        return self::TYPE_REF;
    }

    public function getResolvedType(): ?Type
    {
        return $this->resolvedType;
    }
}
