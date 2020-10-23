<?php

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\Cartridge\EventEngine\Code\Metadata;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\GraphMl\Metadata\Command;
use OpenCodeModeling\JsonSchemaToPhp\Type\ArrayType;
use OpenCodeModeling\JsonSchemaToPhp\Type\NumberType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ReferenceType;
use OpenCodeModeling\JsonSchemaToPhp\Type\StringType;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class JsonSchemaTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_supports_object_type(): void
    {
        $metadata = Command::fromJsonMetadata(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'schema_with_object.json')
        );

        $vertex = $this->prophesize(CommandType::class);
        $vertex->metadataInstance()->willReturn($metadata);

        $schema = JsonSchema::fromVertex($vertex->reveal());

        $typeSet = $schema->type();

        /** @var ObjectType $type */
        $type = $typeSet->first();

        $this->assertInstanceOf(ObjectType::class, $type);

        $properties = $type->properties();

        $this->assertArrayHasKey('buildingId', $properties);
        $this->assertArrayHasKey('name', $properties);

        $buildingIdTypeSet = $properties['buildingId'];

        /** @var StringType $buildingId */
        $buildingId = $buildingIdTypeSet->first();

        $this->assertInstanceOf(StringType::class, $buildingId);

        $this->assertSame('buildingId', $buildingId->name());
        $this->assertSame('string', $buildingId->type());
        $this->assertSame(
            '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
            $buildingId->pattern()
        );
        $this->assertTrue($buildingId->isRequired());
        $this->assertFalse($buildingId->isNullable());

        $nameTypeSet = $properties['name'];

        /** @var ReferenceType $name */
        $name = $nameTypeSet->first();

        $this->assertInstanceOf(ReferenceType::class, $name);

        /** @var StringType $resolvedType */
        $resolvedType = $name->resolvedType()->first();
        $this->assertInstanceOf(StringType::class, $resolvedType);

        $this->assertSame('name', $resolvedType->name());
        $this->assertSame('string', $resolvedType->type());
        $this->assertNull($resolvedType->pattern());
        $this->assertTrue($resolvedType->isRequired());
        $this->assertTrue($resolvedType->isNullable());
    }

    /**
     * @test
     */
    public function it_supports_array_type(): void
    {
        $metadata = Command::fromJsonMetadata(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'schema_with_array.json')
        );

        $vertex = $this->prophesize(CommandType::class);
        $vertex->metadataInstance()->willReturn($metadata);

        $schema = JsonSchema::fromVertex($vertex->reveal());

        $typeSet = $schema->type();

        /** @var ArrayType $type */
        $type = $typeSet->first();

        $this->assertInstanceOf(ArrayType::class, $type);

        $items = $type->items();

        $this->assertCount(3, $items);

        $this->assertInstanceOf(NumberType::class, $items[0]->first());

        $this->assertInstanceOf(StringType::class, $items[1]->first());

        $this->assertInstanceOf(ReferenceType::class, $items[2]->first());
        $this->assertSame(2, $items[2]->first()->resolvedType()->first()->minLength());

        $this->assertInstanceOf(StringType::class, $type->additionalItems()->first());
    }
}
