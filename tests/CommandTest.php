<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Command;
use EventEngine\CodeGenerator\EventEngineAst\Config\EventEngineConfig;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredNaming;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class CommandTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $config = new EventEngineConfig();
        $config->setBasePath($this->basePath);
        $config->setClassInfoList($this->classInfoList);

        $this->config = new PreConfiguredNaming($config);
    }

    /**
     * @test
     */
    public function it_creates_api_command_description(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateApiDescription(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertApiDescription($file);
        }
    }

    private function assertApiDescription(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Api;
        
        use EventEngine\EventEngine;
        use EventEngine\EventEngineDescription;
        use EventEngine\JsonSchema\JsonSchemaArray;
        final class Command implements EventEngineDescription
        {
            public const ADD_BUILDING = 'AddBuilding';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerCommand(self::ADD_BUILDING, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Building/Command/AddBuilding.json'));
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_command_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateApiDescriptionClassMap(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertApiDescriptionClassMap($file);
        }
    }

    private function assertApiDescriptionClassMap(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Api;
        
        use EventEngine\EventEngine;
        use EventEngine\EventEngineDescription;
        use EventEngine\JsonSchema\JsonSchemaArray;
        use MyService\Domain\Model\Building\Command\AddBuilding;
        final class Command implements EventEngineDescription
        {
            public const CLASS_MAP = [self::ADD_BUILDING => AddBuilding::class];
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_command_description_with_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateApiDescription(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer,
            $fileCollection
        );
        $command->generateApiDescriptionClassMap(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertApiDescriptionWithClassMap($file);
        }
    }

    private function assertApiDescriptionWithClassMap(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Api;
        
        use EventEngine\EventEngine;
        use EventEngine\EventEngineDescription;
        use EventEngine\JsonSchema\JsonSchemaArray;
        use MyService\Domain\Model\Building\Command\AddBuilding;
        final class Command implements EventEngineDescription
        {
            public const ADD_BUILDING = 'AddBuilding';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public const CLASS_MAP = [self::ADD_BUILDING => AddBuilding::class];
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerCommand(self::ADD_BUILDING, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Building/Command/AddBuilding.json'));
            }
        }
        EOF;

        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_command_json_schema_file(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $command = new Command($this->config);

        $files = $command->generateJsonSchemaFile(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer
        );

        $filename = '/service/src/Domain/Api/_schema/Building/Command/AddBuilding.json';
        $this->assertCount(1, $files);

        $this->assertArrayHasKey($filename, $files);
        $this->assertArrayHasKey('code', $files[$filename]);
        $this->assertArrayHasKey('filename', $files[$filename]);

        $json = <<<JSON
        {
            "newAggregate": true,
            "voNamespace": "Building",
            "type": "object",
            "properties": {
                "buildingId": {
                    "\$ref": "#\/definitions\/BuildingId",
                    "namespace": "\/"
                },
                "name": {
                    "type": "string",
                    "namespace": "\/Building"
                }
            },
            "required": [
                "buildingId",
                "name"
            ],
            "additionalProperties": false,
            "name": "Add Building"
        }
        JSON;

        $this->assertSame('/service/src/Domain/Api/_schema/Building/Command/AddBuilding.json', $files[$filename]['filename']);
        $this->assertSame($json, $files[$filename]['code']);
    }

    /**
     * @test
     */
    public function it_creates_command_file_with_value_objects(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateCommandFile(
            $this->analyzer->connection($connection->from()->current()->id()),
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(2, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'AddBuilding':
                    $this->assertCommandFile($file);
                    break;
                case 'BuildingId':
                    $this->assertSame('MyService\Domain\Model\ValueObject', $file->getNamespace());
                    break;
                case 'Name':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Building', $file->getNamespace());
                    break;
                default:
                    $this->assertTrue(false, \sprintf('Class "%s" not checked', $file->getName()));
                    break;
            }
        }
    }

    private function assertCommandFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Model\Building\Command;
        
        use EventEngine\Data\ImmutableRecord;
        use EventEngine\Data\ImmutableRecordLogic;
        use MyService\Domain\Model\ValueObject\BuildingId;
        use MyService\Domain\Model\ValueObject\Building\Name;
        final class AddBuilding implements ImmutableRecord
        {
            use ImmutableRecordLogic;
            public const BUILDING_ID = 'buildingId';
            public const NAME = 'name';
            private BuildingId $buildingId;
            private Name $name;
            public function buildingId() : BuildingId
            {
                return $this->buildingId;
            }
            public function name() : Name
            {
                return $this->name;
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
