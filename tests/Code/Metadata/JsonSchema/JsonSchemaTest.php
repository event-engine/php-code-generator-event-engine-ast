<?php

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\JsonSchema;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\ArrayType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\NumberType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\ObjectType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\ReferenceType;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\Metadata\JsonSchema\Type\StringType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\GraphMl\Metadata\Command;
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

        /** @var ObjectType $type */
        $type = $schema->type();

        $this->assertInstanceOf(ObjectType::class, $type);

        $properties = $type->properties();

        $this->assertArrayHasKey('buildingId', $properties);
        $this->assertArrayHasKey('name', $properties);

        /** @var StringType $buildingId */
        $buildingId = $properties['buildingId'];

        $this->assertInstanceOf(StringType::class, $buildingId);

        $this->assertSame('buildingId', $buildingId->getName());
        $this->assertSame('string', $buildingId->getType());
        $this->assertSame(
            '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
            $buildingId->getPattern()
        );
        $this->assertTrue($buildingId->isRequired());
        $this->assertFalse($buildingId->isNullable());

        /** @var ReferenceType $name */
        $name = $properties['name'];
        $this->assertInstanceOf(ReferenceType::class, $name);

        /** @var StringType $name */
        $resolvedType = $name->getResolvedType();
        $this->assertInstanceOf(StringType::class, $resolvedType);

        $this->assertSame('name', $resolvedType->getName());
        $this->assertSame('string', $resolvedType->getType());
        $this->assertNull($resolvedType->getPattern());
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

        /** @var ArrayType $type */
        $type = $schema->type();

        $this->assertInstanceOf(ArrayType::class, $type);

        $items = $type->getItems();

        $this->assertCount(3, $items);

        $this->assertInstanceOf(NumberType::class, $items[0]);

        $this->assertInstanceOf(StringType::class, $items[1]);

        $this->assertInstanceOf(ReferenceType::class, $items[2]);
        $this->assertSame(2, $items[2]->getResolvedType()->getMinLength());

        $this->assertInstanceOf(StringType::class, $type->getAdditionalItems());
    }
}
