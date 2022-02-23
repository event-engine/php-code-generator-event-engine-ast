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
use EventEngine\CodeGenerator\EventEngineAst\Query;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class QueryTest extends BaseTestCase
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
    public function it_generates_query_and_resolver_and_finder(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_id_vo.json'));
        $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $connection = $this->analyzer->analyse($node);

        $query = new Query($this->config);

        $fileCollection = FileCollection::emptyList();

        $query->generate(
            $connection,
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(4, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'GetBuilding':
                    $this->assertQuery($file);

                    break;
                case 'BuildingResolver':
                    $this->assertResolver($file);

                    break;
                case 'BuildingFinder':
                    $this->assertFinderWithState($file);

                    break;
                case 'BuildingId':
                    break;
                default:
                    $this->assertFalse(true, 'Unintended class generated: ' . $file->getName());

                    break;
            }
        }
    }

    private function assertQuery(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Infrastructure\Resolver\Query;

        use EventEngine\Data\ImmutableRecord;
        use EventEngine\Data\ImmutableRecordLogic;
        use MyService\Domain\Model\ValueObject\BuildingId;
        final class GetBuilding implements ImmutableRecord
        {
            use ImmutableRecordLogic;
            public const BUILDING_ID = 'buildingId';
            private BuildingId $buildingId;
            public function buildingId() : BuildingId
            {
                return $this->buildingId;
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function assertResolver(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Infrastructure\Resolver;

        use EventEngine\Messaging\Message;
        use EventEngine\Querying\Resolver;
        use MyService\Domain\Model\ValueObject\Building;
        use MyService\Infrastructure\Finder\BuildingFinder;
        use MyService\Infrastructure\Resolver\Query\GetBuilding;
        final class BuildingResolver implements Resolver
        {
            private BuildingFinder $finder;
            public function __construct(BuildingFinder $finder)
            {
                $this->finder = $finder;
            }
            public function resolve(Message $query) : Building
            {
                /** @var GetBuilding $findBy */
                $findBy = GetBuilding::fromArray($query->payload());
                return $this->finder->findBuilding($findBy->buildingId());
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function assertFinderWithState(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Infrastructure\Finder;

        use EventEngine\DocumentStore\DocumentStore;
        use MyService\Domain\Model\ValueObject\Building;
        use MyService\Domain\Model\ValueObject\BuildingId;
        use MyService\Infrastructure\Collection;
        final class BuildingFinder
        {
            private DocumentStore $documentStore;
            public function __construct(DocumentStore $documentStore)
            {
                $this->documentStore = $documentStore;
            }
            public function findBuilding(BuildingId $buildingId) : ?Building
            {
                $doc = $this->documentStore->getDoc(Collection::BUILDINGS, $buildingId->toString());
                if ($doc !== null) {
                    return Building::fromArray($doc['state']);
                }
                return null;
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_type_description(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $connection = $this->analyzer->analyse($node);

        $query = new Query($this->config);

        $fileCollection = FileCollection::emptyList();

        $query->generateApiDescription(
            $connection,
            $this->analyzer,
            $fileCollection
        );

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
        use EventEngine\JsonSchema\Type\TypeRef;
        final class Query implements EventEngineDescription
        {
            public const BUILDING = 'Building';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerQuery(self::BUILDING, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Query/Building.json'))->resolveWith(BuildingResolver::class)->setReturnType(new TypeRef(Type::BUILDING));
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_query_description_with_class_map(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $connection = $this->analyzer->analyse($node);

        $query = new Query($this->config);

        $fileCollection = FileCollection::emptyList();

        $query->generateApiDescription(
            $connection,
            $this->analyzer,
            $fileCollection
        );
        $query->generateApiDescriptionClassMap(
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
        use EventEngine\JsonSchema\JsonSchemaArray;
        use EventEngine\JsonSchema\Type\TypeRef;
        use MyService\Infrastructure\Resolver\BuildingResolver;
        use MyService\Infrastructure\Resolver\Query\GetBuilding;
        final class Query implements EventEngineDescription
        {
            public const BUILDING = 'Building';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public const CLASS_MAP = [self::BUILDING => GetBuilding::class];
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerQuery(self::BUILDING, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/Query/Building.json'))->resolveWith(BuildingResolver::class)->setReturnType(new TypeRef(Type::BUILDING));
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_document_json_schema_file(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_added.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $connection = $this->analyzer->analyse($node);

        $query = new Query($this->config);

        $files = $query->generateJsonSchemaFile(
            $connection,
            $this->analyzer
        );

        $this->assertCount(2, $files);
        $filenameValueObject = '/service/src/Domain/Api/_schema/ValueObject/Building.json';
        $filenameQuery = '/service/src/Domain/Api/_schema/Query/Building.json';

        $this->assertArrayHasKey($filenameValueObject, $files);
        $this->assertArrayHasKey($filenameQuery, $files);

        $this->assertArrayHasKey('code', $files[$filenameValueObject]);
        $this->assertArrayHasKey('filename', $files[$filenameValueObject]);

        $json = <<<'JSON'
        {
            "aggregateState": true,
            "collection": "buildings",
            "voNamespace": "NotUsed",
            "ns": "\/",
            "type": "object",
            "properties": {
                "buildingId": {
                    "$ref": "#\/definitions\/BuildingId",
                    "namespace": "\/"
                },
                "name": {
                    "$ref": "#\/definitions\/Building\/Name",
                    "namespace": "\/Building"
                }
            },
            "required": [
                "buildingId",
                "name"
            ],
            "additionalProperties": false,
            "name": "Building "
        }
        JSON;

        $this->assertSame($filenameValueObject, $files[$filenameValueObject]['filename']);
        $this->assertSame($json, $files[$filenameValueObject]['code']);

        $this->assertArrayHasKey('code', $files[$filenameQuery]);
        $this->assertArrayHasKey('filename', $files[$filenameQuery]);

        $json = <<<'JSON'
        {
            "aggregateState": true,
            "collection": "buildings",
            "voNamespace": "NotUsed",
            "ns": "\/",
            "type": "object",
            "properties": {
                "buildingId": {
                    "$ref": "#\/definitions\/BuildingId",
                    "namespace": "\/"
                }
            },
            "required": [
                "buildingId"
            ],
            "additionalProperties": false,
            "name": "Building "
        }
        JSON;

        $this->assertSame($filenameQuery, $files[$filenameQuery]['filename']);
        $this->assertSame($json, $files[$filenameQuery]['code']);
    }
}
