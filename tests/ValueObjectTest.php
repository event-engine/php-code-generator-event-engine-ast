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
        namespace MyService\Domain\Model\ValueObject\Building;

        use EventEngine\Data\ImmutableRecord;
        use EventEngine\Data\ImmutableRecordLogic;
        use MyService\Domain\Model\ValueObject\Building\Common\BuildingId;
        use MyService\Domain\Model\ValueObject\Common\Name;
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
}
