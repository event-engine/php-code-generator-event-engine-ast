<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Command;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredCommand;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\Filter\FilterFactory;
use PhpParser\NodeTraverser;

final class CommandTest extends BaseTestCase
{
    private PreConfiguredCommand $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->config = new PreConfiguredCommand();
        $this->config->setBasePath($this->basePath);
        $this->config->setClassInfoList($this->classInfoList);
    }

    /**
     * @test
     */
    public function it_creates_api_command_description(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateApiDescription(
            $analyzer,
            $fileCollection,
            $this->apiCommandFilename,
            '/service/src/Domain/Api/_schema/ADD_BUILDING.json'
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
final class Command implements EventEngineDescription
{
    public const ADD_BUILDING = 'add_building';
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->registerCommand(self::ADD_BUILDING, new JsonSchemaArray(\json_decode(file_get_contents('/service/src/Domain/Api/_schema/ADD_BUILDING.json'), true, 512, \JSON_THROW_ON_ERROR)));
    }
}
PHP;
        $this->assertSame($expected, $this->config->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_command_json_schema_file(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $command = new Command($this->config);

        $files = $command->generateJsonSchemaFiles($analyzer, '/service/src/Domain/Api/_schema');

        $this->assertCount(1, $files);

        $this->assertArrayHasKey('ADD_BUILDING', $files);
        $this->assertArrayHasKey('code', $files['ADD_BUILDING']);
        $this->assertArrayHasKey('filename', $files['ADD_BUILDING']);

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

        $this->assertSame('/service/src/Domain/Api/_schema/ADD_BUILDING.json', $files['ADD_BUILDING']['filename']);
        $this->assertSame($json, $files['ADD_BUILDING']['code']);
    }

    /**
     * @test
     */
    public function it_creates_command_file_with_value_objects(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $command = new Command($this->config);

        $fileCollection = FileCollection::emptyList();

        $command->generateCommandFile($analyzer, $fileCollection);

        $this->config->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(3, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'AddBuilding':
                    $this->assertCommandFile($file);
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

    private function assertCommandFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->getParser());

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building\Command;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;
use MyService\Domain\Model\ValueObject\BuildingId;
use MyService\Domain\Model\ValueObject\Name;
final class AddBuilding implements ImmutableRecord
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
