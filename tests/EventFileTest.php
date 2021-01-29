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

final class EventFileTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_event_file(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $codeList = $this->eventFactory->componentFile()(
            $analyzer,
            $this->modelPath
        );

        $this->assertCount(1, $codeList);
        $this->assertFile($codeList);
    }

    private function assertFile(array $codeList): void
    {
        $this->assertArrayHasKey('BUILDING_ADDED', $codeList);
        $this->assertSame('/service/src/Domain/Model/Building/Event/BuildingAdded.php', $codeList['BUILDING_ADDED']['filename']);

        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Model\Building\Event;

use EventEngine\Data\ImmutableRecordLogic;
use EventEngine\Data\ImmutableRecord;
final class BuildingAdded implements ImmutableRecord
{
    use ImmutableRecordLogic;
}
PHP;
        $this->assertSame($expected, $codeList['BUILDING_ADDED']['code']);
    }
}