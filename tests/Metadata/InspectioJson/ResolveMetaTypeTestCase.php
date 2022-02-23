<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\InspectioGraph\VertexConnection;
use EventEngineTest\CodeGenerator\EventEngineAst\BaseTestCase;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ReferenceType;
use OpenCodeModeling\JsonSchemaToPhp\Type\StringType;

abstract class ResolveMetaTypeTestCase extends BaseTestCase
{
    protected function assertResolvedMetadataReferencyTypes(
        VertexConnection $connection,
        string $metadataFqcn,
        bool $nameIsReference = false
    ): void {
        $metadata = $connection->identity()->metadataInstance();
        $this->assertNotNull($metadata);
        $this->assertInstanceOf($metadataFqcn, $metadata);

        $type = $metadata->typeSet()->first();
        $this->assertInstanceOf(ObjectType::class, $type);

        foreach ($type->properties() as $propertyName => $property) {
            switch ($propertyName) {
                case 'buildingId':
                    $propertyType = $property->first();
                    $this->assertInstanceOf(ReferenceType::class, $propertyType);

                    $this->assertSame('#/definitions/BuildingId', $propertyType->ref());

                    $resolvedTypeSet = $propertyType->resolvedType();
                    $this->assertNotNull($resolvedTypeSet);

                    $resolvedType = $resolvedTypeSet->first();
                    $this->assertInstanceOf(StringType::class, $resolvedType);
                    $this->assertSame('Building Id', $resolvedType->name());

                    break;
                case 'name':
                    $propertyType = $property->first();

                    if ($nameIsReference) {
                        $this->assertInstanceOf(ReferenceType::class, $propertyType);

                        $this->assertSame('#/definitions/Building/Name', $propertyType->ref());

                        $resolvedTypeSet = $propertyType->resolvedType();
                        $this->assertNotNull($resolvedTypeSet);

                        $resolvedType = $resolvedTypeSet->first();
                        $this->assertInstanceOf(StringType::class, $resolvedType);
                        $this->assertSame('Building', $resolvedType->custom()['ns'], 'Wrong reference type');
                        $this->assertSame('Name', $resolvedType->name());
                        $this->assertNull($resolvedType->minLength(), 'Wrong reference type');
                    } else {
                        $this->assertInstanceOf(StringType::class, $propertyType);
                        $this->assertSame('name', $propertyType->name());
                    }

                    break;
                default:
                    $this->assertFalse(true, \sprintf('Property "%s" not expected', $propertyName));
            }
        }
    }
}
