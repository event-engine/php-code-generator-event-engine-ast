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
use EventEngine\CodeGenerator\EventEngineAst\ValueObject;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class ValueObjectTest extends BaseTestCase
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
    public function it_generates_scalar_value_object(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));
        $connection = $this->analyzer->analyse($node);

        $valueObject = new ValueObject($this->config);

        $fileCollection = FileCollection::emptyList();

        $valueObject->generate(
            $connection,
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertNameValueObject($file);
        }
    }

    private function assertNameValueObject(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Domain\Model\ValueObject\Building;
        
        final class Name
        {
            private string $name;
            public static function fromString(string $name) : self
            {
                return new self($name);
            }
            private function __construct(string $name)
            {
                $this->name = $name;
            }
            public function toString() : string
            {
                return $this->name;
            }
            /**
             * @param mixed $other
             */
            public function equals($other) : bool
            {
                if (!$other instanceof self) {
                    return false;
                }
                return $this->name === $other->name;
            }
            public function __toString() : string
            {
                return $this->name;
            }
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_immutable_record_value_object(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $connection = $this->analyzer->analyse($node);

        $valueObject = new ValueObject($this->config);

        $fileCollection = FileCollection::emptyList();

        $valueObject->generate(
            $connection,
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(1, $fileCollection);

        foreach ($fileCollection as $file) {
            $this->assertBuildingValueObject($file);
        }
    }

    private function assertBuildingValueObject(ClassBuilder $classBuilder): void
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
        use MyService\Domain\Model\ValueObject\Building\Name;
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
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_creates_api_type_description(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));
        $connection = $this->analyzer->analyse($node);

        $valueObject = new ValueObject($this->config);

        $fileCollection = FileCollection::emptyList();

        $valueObject->generateApiDescription(
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
        final class Type implements EventEngineDescription
        {
            public const BUILDING_NAME = 'Building/Name';
            private const SCHEMA_PATH = 'src/Domain/Api/_schema';
            public static function describe(EventEngine $eventEngine) : void
            {
                $eventEngine->registerType(self::BUILDING_NAME, JsonSchemaArray::fromFile(self::SCHEMA_PATH . '/ValueObject/Building/Name.json'));
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
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));
        $connection = $this->analyzer->analyse($node);

        $valueObject = new ValueObject($this->config);

        $files = $valueObject->generateJsonSchemaFile(
            $connection,
            $this->analyzer
        );

        $this->assertCount(1, $files);
        $filename = '/service/src/Domain/Api/_schema/ValueObject/Building/Name.json';
        $this->assertArrayHasKey('code', $files[$filename]);
        $this->assertArrayHasKey('filename', $files[$filename]);

        $json = <<<JSON
        {
            "ns": "Building",
            "type": "string",
            "name": "Name"
        }
        JSON;

        $this->assertSame('/service/src/Domain/Api/_schema/ValueObject/Building/Name.json', $files[$filename]['filename']);
        $this->assertSame($json, $files[$filename]['code']);
    }

    /**
     * @test
     */
    public function it_generates_value_objects_in_different_namespace(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_info.json'));
        $connectionInfo = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_info_image.json'));
        $connectionImage = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_info_image_crop.json'));
        $connectionCrop = $this->analyzer->analyse($node);

        $valueObject = new ValueObject($this->config);

        $fileCollection = FileCollection::emptyList();

        $valueObject->generate(
            $connectionImage,
            $this->analyzer,
            $fileCollection
        );

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(10, $fileCollection);

        /** @var ClassBuilder $file */
        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'Image':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image', $file->getFqcn());
                    break;
                case 'Alt':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image\Alt', $file->getFqcn());
                    break;
                case 'CropList':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image\CropList', $file->getFqcn());
                    break;
                case 'MediaAsset':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image\MediaAsset', $file->getFqcn());
                    break;
                case 'Src':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\Image\Src', $file->getFqcn());
                    break;
                case 'Height':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop\Height', $file->getFqcn());
                    break;
                case 'ImageCrop':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop\ImageCrop', $file->getFqcn());
                    break;
                case 'Width':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop\Width', $file->getFqcn());
                    break;
                case 'X':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop\X', $file->getFqcn());
                    break;
                case 'Y':
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop', $file->getNamespace());
                    $this->assertSame('MyService\Domain\Model\ValueObject\Common\MediaAsset\Crop\Y', $file->getFqcn());
                    break;
                default:
                    $this->assertFalse(true, 'Unexpected class generated');
                    break;
            }
        }
    }
}
