<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\AggregateBehaviourFactory;
use EventEngine\CodeGenerator\EventEngineAst\AggregateStateFactory;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\JsonNode;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use OpenCodeModeling\Filter\FilterFactory;

final class AggregateBehaviourFileTest extends BaseTestCase
{
    private AggregateStateFactory $aggregateStateFactory;
    private AggregateBehaviourFactory $aggregateBehaviourFactory;

    private string $apiFilename;
    private string $aggregatePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->apiFilename = $this->srcFolder . '/Domain/Api/Aggregate.php';
        $this->aggregatePath = $this->srcFolder . '/Domain/Model';

        $this->aggregateStateFactory = AggregateStateFactory::withDefaultConfig(
            FilterFactory::constantNameFilter(),
            FilterFactory::constantValueFilter(),
            FilterFactory::directoryToNamespaceFilter(),
        );

        $classInfoList = new ClassInfoList();

        $classInfoList->addClassInfo(
                ...Psr4Info::fromComposer(
                    '/service',
                    $this->fileSystem->read('service/composer.json'),
                    $this->aggregateStateFactory->config()->getFilterDirectoryToNamespace(),
                    $this->aggregateStateFactory->config()->getFilterNamespaceToDirectory()
                )
            );

        $this->aggregateStateFactory->config()->setClassInfoList($classInfoList);

        $this->aggregateBehaviourFactory = AggregateBehaviourFactory::withDefaultConfig(
            FilterFactory::constantNameFilter(),
            FilterFactory::constantValueFilter(),
            FilterFactory::directoryToNamespaceFilter(),
            $this->aggregateStateFactory->config()
        );

        $this->aggregateBehaviourFactory->config()->setClassInfoList($classInfoList);
    }

    /**
     * @test
     */
    public function it_creates_aggregate_behaviour_file(): void
    {
        $aggregate = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $analyzer = new EventSourcingAnalyzer($aggregate, FilterFactory::constantNameFilter(), $this->metadataFactory);

        $codeList = $this->aggregateBehaviourFactory->componentFile()(
            $analyzer,
            $this->aggregatePath,
            $this->aggregatePath,
            $this->srcFolder . '/Domain/Api/Event.php'
        );

        $this->assertCount(1, $codeList);

        $this->assertFile($codeList);
    }

    private function assertFile(array $codeList)
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
}
PHP;
        $this->assertSame($expected, $codeList['BUILDING']['code']);
    }
}
