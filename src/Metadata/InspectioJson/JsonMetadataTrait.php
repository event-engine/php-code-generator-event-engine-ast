<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Exception\ErrorParsingMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasQueryTypeSet;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\Metadata\HasQuery;
use EventEngine\InspectioGraph\Metadata\HasSchema;
use EventEngine\InspectioGraph\VertexConnectionMap;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\JsonSchemaToPhp\Exception\RuntimeException;
use OpenCodeModeling\JsonSchemaToPhp\Shorthand\Shorthand;
use OpenCodeModeling\JsonSchemaToPhp\Type\AllOfType;
use OpenCodeModeling\JsonSchemaToPhp\Type\AnyOfType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ArrayType;
use OpenCodeModeling\JsonSchemaToPhp\Type\NotType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\OneOfType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ReferenceType;
use OpenCodeModeling\JsonSchemaToPhp\Type\Type;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

trait JsonMetadataTrait
{
    private ?array $schema = null;

    private ?TypeSet $typeSet = null;

    private array $customData = [];

    /**
     * @param string $json
     * @param string $name
     * @return static
     * @throws \JsonException
     */
    public static function fromJsonMetadata(string $json, string $name)
    {
        $self = new self();

        $data = MetadataFactory::decodeJson($json);

        $self->customData = $data;

        if (! $self instanceof HasSchema) {
            return $self;
        }

        unset($self->customData['schema'], $self->customData['query'], $self->customData['querySchema'], $self->customData['uiSchema'], $self->customData['shorthand']);

        $schema = $data['schema'] ?? null;
        $querySchema = $data['query'] ?? $data['querySchema'] ?? null;

        if ($schema !== null && ($data['shorthand'] ?? false)) {
            $schema = Shorthand::convertToJsonSchema($schema, $self->customData);
        }
        if ($querySchema !== null && ($data['shorthand'] ?? false)) {
            $querySchema = Shorthand::convertToJsonSchema($querySchema, $self->customData);
        }

        $self->schema = $schema;

        if (! empty($self->schema) && $self instanceof HasTypeSet) {
            if (! isset($self->schema['name'])) {
                $self->schema['name'] = $name;
            }

            try {
                $self->typeSet = Type::fromDefinition($self->schema);
            } catch (RuntimeException $e) {
                throw ErrorParsingMetadata::withError(
                    \sprintf(
                        'Could not create JSON schema type set from JSON schema definition. Error: %s (%s:%d)',
                        $e->getMessage(), $e->getFile(), $e->getLine()
                    ),
                    $e
                );
            }
        }

        if (! empty($querySchema) && $self instanceof HasQuery) {
            if (! isset($querySchema['name'])) {
                $querySchema['name'] = $name;
            }
            $self->query = $querySchema;
        }

        if (! empty($querySchema) && $self instanceof HasQueryTypeSet) {
            if (! isset($querySchema['name'])) {
                $querySchema['name'] = $name;
            }

            try {
                $self->queryTypeSet = Type::fromDefinition($querySchema);
            } catch (RuntimeException $e) {
                throw ErrorParsingMetadata::withError(
                    \sprintf(
                        'Could not create JSON schema type set from JSON schema definition. Error: %s (%s:%d)',
                        $e->getMessage(), $e->getFile(), $e->getLine()
                    ),
                    $e
                );
            }
        }

        return $self;
    }

    public function resolveMetadataReferences(VertexConnectionMap $vertexConnectionMap, callable $filterName): void
    {
        if ($this->typeSet !== null) {
            $this->resolveTypes($this->typeSet, $vertexConnectionMap, $filterName);
        }

        if ($this instanceof HasQueryTypeSet && $this->queryTypeSet !== null) {
            $this->resolveTypes($this->queryTypeSet, $vertexConnectionMap, $filterName);
        }
    }

    private function resolveTypes(TypeSet $typeSet, VertexConnectionMap $vertexConnectionMap, callable $filterName): void
    {
        foreach ($typeSet->types() as $type) {
            switch (true) {
                case $type instanceof ObjectType:
                    foreach ($type->properties() as $property) {
                        $this->resolveTypes($property, $vertexConnectionMap, $filterName);
                    }
                    break;
                case $type instanceof ArrayType:
                    foreach ($type->items() as $item) {
                        $this->resolveTypes($item, $vertexConnectionMap, $filterName);
                    }
                    foreach ($type->definitions() as $definition) {
                        $this->resolveTypes($definition, $vertexConnectionMap, $filterName);
                    }
                    foreach ($type->contains() as $contain) {
                        $this->resolveTypes($contain, $vertexConnectionMap, $filterName);
                    }
                    if ($type->additionalItems() !== null) {
                        $this->resolveTypes($type->additionalItems(), $vertexConnectionMap, $filterName);
                    }
                    break;
                case $type instanceof AllOfType:
                case $type instanceof AnyOfType:
                case $type instanceof OneOfType:
                    foreach ($type->getTypeSets() as $ofTypeSet) {
                        $this->resolveTypes($ofTypeSet, $vertexConnectionMap, $filterName);
                    }
                    break;
                case $type instanceof NotType:
                    $this->resolveTypes($type->getTypeSet(), $vertexConnectionMap, $filterName);
                    break;
                default:
                    break;
            }

            if (! $type instanceof ReferenceType || $type->resolvedType() !== null) {
                return;
            }

            $namespace = \trim($type->custom()['ns'] ?? $type->custom()['namespace'] ?? '', '/');

            $name = ($filterName)($type->extractNameFromReference());
            $documents = $vertexConnectionMap->filterByNameAndType($name, VertexType::TYPE_DOCUMENT);

            foreach ($documents as $document) {
                $documentMetadata = $document->identity()->metadataInstance();

                $documentNamespace = \trim($documentMetadata->customData()['ns'] ?? $type->custom()['namespace'] ?? '', '/');

                if ($namespace === $documentNamespace
                    && $documentMetadata instanceof HasTypeSet
                    && ($docTypeSet = $documentMetadata->typeSet())
                ) {
                    $type->setResolvedType($docTypeSet);
                }
            }
        }
    }
}
