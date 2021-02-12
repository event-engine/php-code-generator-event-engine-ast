<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Aggregate;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredAggregate;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\Filter\FilterFactory;
use PhpParser\NodeTraverser;

final class AggregateTest extends BaseTestCase
{
    private PreConfiguredAggregate $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->config = new PreConfiguredAggregate();
        $this->config->setBasePath($this->basePath);
        $this->config->setClassInfoList($this->classInfoList);
    }

    /**
     * @test
     */
    public function it_creates_api_aggregate_description(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateApiDescription(
            $analyzer,
            $fileCollection,
            $this->apiAggregateFilename
        );

        $this->config->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertApiDescription($file);
        }
    }

    private function assertApiDescription(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->getParser());

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\JsonSchemaArray;
use MyService\Domain\Model\Building\Building;
final class Aggregate implements EventEngineDescription
{
    public const BUILDING = 'building';
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->process(Command::ADD_BUILDING)->withNew(self::BUILDING)->identifiedBy('buildingId')->handle([Building::class, 'addBuilding'])->recordThat(Event::BUILDING_ADDED)->apply([Building::class, 'whenBuildingAdded'])->storeStateIn('buildings');
    }
}
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_aggregate_file_with_value_objects(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateAggregateFile($analyzer, $fileCollection, $this->apiEventFilename);

        $this->config->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(3, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'Building':
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
        $ast = $this->config->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->getParser());

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building;

use EventEngine\Messaging\Message;
use Generator;
use MyService\Domain\Api\Event;
use MyService\Domain\Model\Building\BuildingState;
final class Building
{
    public static function addBuilding(Message $addBuilding) : Generator
    {
        (yield [Event::BUILDING_ADDED, $addBuilding->payload()]);
    }
    public static function whenBuildingAdded(Message $buildingAdded) : BuildingState
    {
        return BuildingState::fromArray($buildingAdded->payload());
    }
}
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_aggregate_state_file(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateAggregateStateFile($analyzer, $fileCollection);

        $this->config->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(3, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'BuildingState':
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
        $ast = $this->config->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->getParser());

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;
use MyService\Domain\Model\ValueObject\BuildingId;
use MyService\Domain\Model\ValueObject\Name;
final class BuildingState implements ImmutableRecord
{
    use ImmutableRecordLogic;
    public const BUILDING_ID = 'building_id';
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
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
