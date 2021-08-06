<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Aggregate;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class AggregateTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_api_aggregate_description(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $this->analyzer->analyse($node);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateApiDescription(
            $connection,
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
        use EventEngine\JsonSchema\JsonSchema;
        use EventEngine\JsonSchema\JsonSchemaArray;
        use MyService\Domain\Model\Building\BuildingBehaviour;
        final class Aggregate implements EventEngineDescription
        {
            public const BUILDING = 'Building';
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->process(Command::ADD_BUILDING)->withNew(self::BUILDING)->identifiedBy('buildingId')->handle([BuildingBehaviour::class, 'addBuilding'])->recordThat(Event::BUILDING_ADDED)->apply([BuildingBehaviour::class, 'whenBuildingAdded'])->storeStateIn('buildings')->storeEventsIn('building_stream');
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_aggregate_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateApiDescriptionClassMap(
            $connection,
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
        use EventEngine\JsonSchema\JsonSchema;
        use EventEngine\JsonSchema\JsonSchemaArray;
        use MyService\Domain\Model\Building\BuildingBehaviour;
        final class Aggregate implements EventEngineDescription
        {
            public const CLASS_MAP = [self::BUILDING => BuildingBehaviour::class];
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_aggregate_description_with_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $this->analyzer->analyse($node);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateApiDescription(
            $connection,
            $this->analyzer,
            $fileCollection
        );
        $aggregate->generateApiDescriptionClassMap(
            $connection,
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
        use EventEngine\JsonSchema\JsonSchema;
        use EventEngine\JsonSchema\JsonSchemaArray;
        use MyService\Domain\Model\Building\BuildingBehaviour;
        final class Aggregate implements EventEngineDescription
        {
            public const BUILDING = 'Building';
            public const CLASS_MAP = [self::BUILDING => BuildingBehaviour::class];
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->process(Command::ADD_BUILDING)->withNew(self::BUILDING)->identifiedBy('buildingId')->handle([BuildingBehaviour::class, 'addBuilding'])->recordThat(Event::BUILDING_ADDED)->apply([BuildingBehaviour::class, 'whenBuildingAdded'])->storeStateIn('buildings')->storeEventsIn('building_stream');
            }
        }
        EOF;

        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_aggregate_file(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $this->analyzer->analyse($node);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateAggregateFile($connection, $this->analyzer, $fileCollection);

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'BuildingBehaviour':
                    $this->assertAggregateFile($file);
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

    private function assertAggregateFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Model\Building;
        
        use EventEngine\Messaging\Message;
        use Generator;
        use MyService\Domain\Api\Command;
        use MyService\Domain\Api\Event;
        use MyService\Domain\Model\ValueObject\Building;
        final class BuildingBehaviour
        {
            public static function addBuilding(Message $addBuilding) : Generator
            {
                (yield [Event::BUILDING_ADDED, $addBuilding->payload()]);
            }
            public static function whenBuildingAdded(Message $buildingAdded) : Building
            {
                return Building::fromArray($buildingAdded->payload());
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_aggregate_state_file_with_value_objects(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $this->analyzer->analyse($node);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateAggregateStateFile($connection, $this->analyzer, $fileCollection);

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(2, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'Building':
                    $this->assertAggregateStateFile($file);
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

    private function assertAggregateStateFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Model\ValueObject;
        
        use EventEngine\Data\ImmutableRecord;
        use EventEngine\Data\ImmutableRecordLogic;
        final class Building implements ImmutableRecord
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
            public function withBuildingAdded() : self
            {
                $instance = clone $this;
                return $instance;
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
