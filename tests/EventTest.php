<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredEvent;
use EventEngine\CodeGenerator\EventEngineAst\Event;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\Filter\FilterFactory;
use PhpParser\NodeTraverser;

final class EventTest extends BaseTestCase
{
    private PreConfiguredEvent $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->config = new PreConfiguredEvent();
        $this->config->setBasePath($this->basePath);
        $this->config->setClassInfoList($this->classInfoList);
    }

    /**
     * @test
     */
    public function it_creates_api_event_description(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateApiDescription(
            $analyzer,
            $fileCollection,
            $this->apiEventFilename,
            '/service/src/Domain/Api/_schema/BUILDING_ADDED.json'
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
final class Event implements EventEngineDescription
{
    public const BUILDING_ADDED = 'building_added';
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->registerEvent(self::BUILDING_ADDED, new JsonSchemaArray(\json_decode(file_get_contents('/service/src/Domain/Api/_schema/BUILDING_ADDED.json'), true, 512, \JSON_THROW_ON_ERROR)));
    }
}
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_event_json_schema_file(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $event = new Event($this->config);

        $files = $event->generateJsonSchemaFiles($analyzer, '/service/src/Domain/Api/_schema');

        $this->assertCount(1, $files);

        $this->assertArrayHasKey('BUILDING_ADDED', $files);
        $this->assertArrayHasKey('code', $files['BUILDING_ADDED']);
        $this->assertArrayHasKey('filename', $files['BUILDING_ADDED']);

        $json = <<<JSON
        {
            "type": "object",
            "properties": {
                "buildingId": {
                    "format": "uuid",
                    "type": "string"
                },
                "name": {
                    "type": "string"
                }
            },
            "required": [
                "buildingId",
                "name"
            ],
            "additionalProperties": false
        }
        JSON;

        $this->assertSame('/service/src/Domain/Api/_schema/BUILDING_ADDED.json', $files['BUILDING_ADDED']['filename']);
        $this->assertSame($json, $files['BUILDING_ADDED']['code']);
    }

    /**
     * @test
     */
    public function it_creates_event_file_with_value_objects(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $event = new Event($this->config);

        $fileCollection = FileCollection::emptyList();

        $event->generateEventFile($analyzer, $fileCollection);

        $this->config->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(3, $fileCollection);

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
        $ast = $this->config->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->getParser());

        $expected = <<<'PHP'
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
}
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
