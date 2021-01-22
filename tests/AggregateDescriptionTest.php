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

final class AggregateDescriptionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_aggregate_description(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $descriptionFile = $this->emptyClassFactory->component()(
            '/service/src/Domain/Api/Aggregate.php'
        );

        $descriptionFile = $this->descriptionFileMethodFactory->component()(
            $descriptionFile
        );

        $descriptionFile = $this->aggregateDescriptionFactory->component()(
            $analyzer,
            $descriptionFile,
            '/service/src/Domain/Model'
        );

        $this->assertFile($descriptionFile);
    }

    private function assertFile(string $code): void
    {
        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Api;

use MyService\Domain\Model\Building\Building;
use EventEngine\JsonSchema\JsonSchemaArray;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\EventEngineDescription;
use EventEngine\EventEngine;
class Aggregate implements EventEngineDescription
{
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->process(Command::ADD_BUILDING)->withNew(self::BUILDING)->identifiedBy('buildingId')->handle([Building::class, 'addBuilding'])->recordThat(Event::BUILDING_ADDED)->apply([Building::class, 'whenBuildingAdded'])->storeStateIn('buildings');
    }
    public const BUILDING = 'building';
}
PHP;
        $this->assertSame($expected, $code);
    }
}
