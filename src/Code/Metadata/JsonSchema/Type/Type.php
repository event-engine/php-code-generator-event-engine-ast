<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type;

abstract class Type
{
    public const TYPE_REF = '$ref';
    public const TYPE_ANY = 'any';
    public const TYPE_ARRAY = 'array';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_OBJECT = 'object';
    public const TYPE_STRING = 'string';

    public const FORMAT_BINARY = 'base64';
    public const FORMAT_DATE = 'date';
    public const FORMAT_DATETIME = 'date-time';
    public const FORMAT_DURATION = 'duration';
    public const FORMAT_INT32 = 'int32';
    public const FORMAT_INT64 = 'int64';
    public const FORMAT_TIME = 'time';
    public const FORMAT_URI = 'uri';

    /**
     * @var bool
     */
    protected $nullable = false;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $isRootSchema = false;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setRootSchema(bool $isRootSchema): self
    {
        $this->isRootSchema = $isRootSchema;

        return $this;
    }

    public function isRootSchema(): bool
    {
        return $this->isRootSchema;
    }

    abstract public function getType(): string;

    public static function fromDefinition(string $name, array $definition): Type
    {
        $self = new static($name);

        if (isset($definition['definitions'])
            && \property_exists($self, 'definitions')
        ) {
            foreach ($definition['definitions'] as $propertyName => $propertyDefinition) {
                $self->definitions[$propertyName] = TypeFactory::createType($propertyName, $propertyDefinition);
            }
        }

        // definitions can be shared and must be cloned to not override defaults e. g. required
        $resolveReference = static function (string $ref) use ($self) {
            $referencePath = \explode('/', $ref);
            $name = \array_pop($referencePath);

            /* @phpstan-ignore-next-line */
            $resolvedType = $self->definitions[$name] ?: null;

            return $resolvedType ? clone $resolvedType : null;
        };

        $populateArrayType = static function (string $key, array $definitionValue) use ($resolveReference, $self) {
            if (isset($definitionValue['type'])) {
                $self->$key[] = TypeFactory::createType('', $definitionValue);

                return;
            }
            foreach ($definitionValue as $propertyDefinition) {
                if (isset($propertyDefinition['type'])) {
                    $self->$key[] = TypeFactory::createType('', $propertyDefinition);
                } elseif (isset($propertyDefinition['$ref'])) {
                    $ref = TypeFactory::createType('', $propertyDefinition);
                    /* @phpstan-ignore-next-line */
                    $ref->resolvedType = $resolveReference($propertyDefinition['$ref']);
                    $self->$key[] = $ref;
                }
            }
        };

        foreach ($definition as $definitionKey => $definitionValue) {
            if (\property_exists($self, $definitionKey)) {
                switch ($definitionKey) {
                    case 'properties':
                        foreach ($definitionValue as $propertyName => $propertyDefinition) {
                            if (isset($propertyDefinition['type'])) {
                                /* @phpstan-ignore-next-line */
                                $self->properties[$propertyName] = TypeFactory::createType($propertyName, $propertyDefinition);
                            } elseif (isset($propertyDefinition['$ref'])) {
                                $ref = TypeFactory::createType('', $propertyDefinition);
                                /* @phpstan-ignore-next-line */
                                $ref->resolvedType = $resolveReference($propertyDefinition['$ref']);
                                /* @phpstan-ignore-next-line */
                                $self->properties[$propertyName] = $ref;
                            }
                        }
                        break;
                    case 'items':
                        $populateArrayType('items', $definitionValue);
                        break;
                    case 'contains':
                        $populateArrayType('contains', $definitionValue);
                        break;
                    case 'additionalItems':
                        /* @phpstan-ignore-next-line */
                        $self->additionalItems = TypeFactory::createType('', $definitionValue);
                        break;
                    case 'definitions':
                        // handled beforehand
                        break;
                    default:
                        $self->$definitionKey = $definitionValue;
                        break;
                }
            }
            if ($definitionKey === '$ref' && \property_exists($self, 'ref')) {
                $self->ref = $definitionValue;
            }
        }

        $populateRequired = static function (string $key) use ($self) {
            if (\property_exists($self, 'required')
                && \property_exists($self, $key)
            ) {
                foreach ($self->required as $requiredName) {
                    if ($self->$key[$requiredName] instanceof SupportsRequired) {
                        $self->$key[$requiredName]->setRequired(true);
                        continue;
                    }
                    if ($self->$key[$requiredName] instanceof ReferenceType) {
                        /* @phpstan-ignore-next-line */
                        $self->$key[$requiredName]->getResolvedType()->setRequired(true);
                        continue;
                    }
                    throw new \RuntimeException(
                        \sprintf(
                            'Property "%s" of type "%s" does not support require.',
                            $requiredName,
                            \get_class($self->$key[$requiredName])
                        )
                    );
                }
            }
        };

        $populateRequired('properties');
        $populateRequired('items');
        $populateRequired('contains');
        $populateRequired('additionalItems');

        return $self;
    }
}
