<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\AggregateMetadata;
use EventEngine\InspectioGraphCody\Aggregate;
use EventEngine\InspectioGraphCody\JsonNode;

final class AggregateMetadataTest extends ResolveMetaTypeTestCase
{
    /**
     * @test
     */
    public function it_resolves_aggregate_metadata_types(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));
        $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_id_vo.json'));
        $this->analyzer->analyse($node);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $this->analyzer->analyse($node);

        $aggregate = $this->analyzer->connection('buTwEKKNLBBo6WAERYN1Gn');
        $this->assertInstanceOf(Aggregate::class, $aggregate->identity());

        $this->assertResolvedMetadataReferencyTypes($aggregate, AggregateMetadata::class);
    }
}
