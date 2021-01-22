<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\Filter\FilterFactory;

final class AggregateStateImmutableRecordOverrideTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_aggregate_state_immutable_record_override(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $codeList = $this->aggregateStateFactory->componentFile()(
            $analyzer,
            $this->modelPath
        );

        $this->assertCount(1, $codeList);

        $codeList = $this->aggregateStateFactory->componentDescriptionImmutableRecordOverride()(
            $analyzer,
            $codeList
        );

        $this->assertCount(1, $codeList);
        $this->assertFile($codeList);
    }

    private function assertFile(array $codeList): void
    {
        $this->assertArrayHasKey('BUILDING_STATE', $codeList);
        $this->assertSame('/service/src/Domain/Model/Building/BuildingState.php', $codeList['BUILDING_STATE']['filename']);

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building;

use EventEngine\Data\ImmutableRecordLogic;
use EventEngine\Data\ImmutableRecord;
final class BuildingState implements ImmutableRecord
{
    use ImmutableRecordLogic;
    private array $state = [];
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
        $this->assertSame($expected, $codeList['BUILDING_STATE']['code']);
    }
}
