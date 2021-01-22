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

final class EventDescriptionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_creates_event_description(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $descriptionFile = $this->emptyClassFactory->component()(
            '/service/src/Domain/Api/Event.php'
        );

        $descriptionFile = $this->descriptionFileMethodFactory->component()(
            $descriptionFile
        );

        $descriptionFile = $this->eventDescriptionFactory->component()(
            $analyzer,
            $descriptionFile,
            ['BUILDING_ADDED' => ['filename' => '/service/src/Domain/Api/_schema/BUILDING_ADDED.json']]
        );

        $this->assertFile($descriptionFile);
    }

    private function assertFile(string $code): void
    {
        $expected = <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Api;

use EventEngine\JsonSchema\JsonSchemaArray;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\EventEngineDescription;
use EventEngine\EventEngine;
class Event implements EventEngineDescription
{
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->registerEvent(self::BUILDING_ADDED, new JsonSchemaArray(\json_decode(file_get_contents('/service/src/Domain/Api/_schema/BUILDING_ADDED.json'), true, 512, \JSON_THROW_ON_ERROR)));
    }
    public const BUILDING_ADDED = 'building_added';
}
PHP;
        $this->assertSame($expected, $code);
    }
}
