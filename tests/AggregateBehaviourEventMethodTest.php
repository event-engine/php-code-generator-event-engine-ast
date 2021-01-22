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

final class AggregateBehaviourEventMethodTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_aggregate_behaviour_event_method(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $codeList = $this->aggregateBehaviourFactory->componentFile()(
            $analyzer,
            $this->modelPath,
            $this->modelPath,
            $this->apiEventFilename
        );
        $this->assertCount(1, $codeList);

        $codeList = $this->aggregateBehaviourFactory->componentEventMethod()(
            $analyzer,
            $codeList
        );

        $this->assertCount(1, $codeList);
        $this->assertFile($codeList);
    }

    private function assertFile(array $codeList): void
    {
        $this->assertArrayHasKey('BUILDING', $codeList);
        $this->assertSame('/service/src/Domain/Model/Building/Building.php', $codeList['BUILDING']['filename']);

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building;

use MyService\Domain\Model\Building\BuildingState as State;
use Generator;
use EventEngine\Messaging\Message;
use MyService\Domain\Api\Event;
final class Building
{
    public static function whenBuildingAdded(Message $buildingAdded) : State
    {
        return State::fromArray($buildingAdded->payload());
    }
}
PHP;
        $this->assertSame($expected, $codeList['BUILDING']['code']);
    }
}
