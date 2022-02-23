<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Aggregate;
use EventEngine\CodeGenerator\EventEngineAst\AggregateStateImmutableRecordOverride;
use EventEngine\CodeGenerator\EventEngineAst\Config\EventEngineConfig;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredNaming;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PhpParser\NodeTraverser;

final class AggregateStateImmutableRecordOverrideTest extends BaseTestCase
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
    public function it_creates_aggregate_state_immutable_record_override(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_without_metadata.json'));
        $connection = $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_state.json'));
        $this->analyzer->analyse($node);

        $override = new AggregateStateImmutableRecordOverride($this->config);
        $aggregate = new Aggregate($this->config);

        $fileCollection = FileCollection::emptyList();

        $aggregate->generateAggregateStateFile($connection, $this->analyzer, $fileCollection);
        $override->generateImmutableRecordOverride($fileCollection);

        $this->config->config()->getObjectGenerator()->sortThings($fileCollection);

        $this->assertCount(2, $fileCollection);

        foreach ($fileCollection as $file) {
            switch ($file->getName()) {
                case 'Building':
                    $this->assertAggregateStateFile($file);

                    break;
                case 'Collection':
                    $this->assertCollectionFile($file);

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

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\ValueObject;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;
final class Building implements ImmutableRecord
{
    use ImmutableRecordLogic;
    private array $state = [];
    public function withBuildingAdded() : self
    {
        $instance = clone $this;
        return $instance;
    }
    public static function fromRecordData(array $recordData)
    {
        return new self($recordData);
    }
    public static function fromArray(array $nativeData)
    {
        return new self(null, $nativeData);
    }
    public function with(array $recordData)
    {
        $copy = clone $this;
        $copy->setRecordData($recordData);
        return $copy;
    }
    public function toArray() : array
    {
        return $this->state;
    }
    public function equals(ImmutableRecord $other) : bool
    {
        return $this->state === $other->toArray();
    }
    private function __construct(array $recordData = null, array $nativeData = null)
    {
        if ($recordData) {
            $this->setRecordData($recordData);
        }
        if ($nativeData) {
            $this->state = array_merge($this->state, $nativeData);
        }
    }
    private function setRecordData(array $recordData) : void
    {
        $this->state = array_merge($this->state, $recordData);
    }
}
PHP;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function assertCollectionFile(ClassBuilder $classBuilder): void
    {
        $ast = $this->config->config()->getParser()->parse('');

        $nodeTraverser = new NodeTraverser();

        $classBuilder->injectVisitors($nodeTraverser, $this->config->config()->getParser());

        $expected = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService\Infrastructure;
        
        final class Collection
        {
            public const BUILDINGS = 'buildings';
        }
        EOF;
        $this->assertSame($expected, $this->config->config()->getPrinter()->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
