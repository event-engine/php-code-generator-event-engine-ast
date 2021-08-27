<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\EventEngineConfig;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredNaming;
use EventEngine\CodeGenerator\EventEngineAst\Event;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class EventTest extends BaseTestCase
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
    public function it_creates_api_event_description(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateApiDescription(
            $this->analyzer->connection($connection->to()->current()->id()),
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
        final class Event implements EventEngineDescription
        {
            public const BUILDING_ADDED = 'BuildingAdded';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerEvent(self::BUILDING_ADDED, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Building/Event/BuildingAdded.json'));
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_event_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateApiDescriptionClassMap(
            $this->analyzer->connection($connection->to()->current()->id()),
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
        use MyService\Domain\Model\Building\Event\BuildingAdded;
        final class Event implements EventEngineDescription
        {
            public const CLASS_MAP = [self::BUILDING_ADDED => BuildingAdded::class];
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_event_description_with_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateApiDescription(
            $this->analyzer->connection($connection->to()->current()->id()),
            $this->analyzer,
            $fileCollection
        );
        $event->generateApiDescriptionClassMap(
            $this->analyzer->connection($connection->to()->current()->id()),
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
        use MyService\Domain\Model\Building\Event\BuildingAdded;
        final class Event implements EventEngineDescription
        {
            public const BUILDING_ADDED = 'BuildingAdded';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public const CLASS_MAP = [self::BUILDING_ADDED => BuildingAdded::class];
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerEvent(self::BUILDING_ADDED, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Building/Event/BuildingAdded.json'));
            }
        }
        EOF;

        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_event_json_schema_file(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $event = new Event($this->config);

        $files = $event->generateJsonSchemaFile(
            $this->analyzer->connection($connection->to()->current()->id()),
            $this->analyzer
        );

        $this->assertCount(1, $files);

        $filename = '/service/src/Domain/Api/_schema/Building/Event/BuildingAdded.json';
        $this->assertArrayHasKey($filename, $files);
        $this->assertArrayHasKey('code', $files[$filename]);
        $this->assertArrayHasKey('filename', $files[$filename]);

        $json = <<<JSON
        {
            "voNamespace": "Building",
            "type": "object",
            "properties": {
                "buildingId": {
                    "\$ref": "#\/definitions\/BuildingId",
                    "namespace": "\/"
                },
                "name": {
                    "type": "string"
                }
            },
            "required": [
                "buildingId",
                "name"
            ],
            "additionalProperties": false,
            "name": "Building Added"
        }
        JSON;

        $this->assertSame('/service/src/Domain/Api/_schema/Building/Event/BuildingAdded.json', $files[$filename]['filename']);
        $this->assertSame($json, $files[$filename]['code']);
    }

    /**
     * @test
     */
    public function it_creates_event_file_with_value_objects(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateEventFile(
            $this->analyzer->connection($connection->to()->current()->id()),
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(2, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'BuildingAdded':
                    $this->assertEventFile($file);
                    break;
                case 'BuildingId':
                case 'Name':
                    break;
                default:
                    $this->assertTrue(false, \sprintf('Class "%s" not checked', $file->getName()));
                    break;
            }
        }
    }

    private function assertEventFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Model\Building\Event;
        
        use EventEngine\Data\ImmutableRecord;
        use EventEngine\Data\ImmutableRecordLogic;
        use MyService\Domain\Model\ValueObject\BuildingId;
        use MyService\Domain\Model\ValueObject\Name;
        final class BuildingAdded implements ImmutableRecord
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
