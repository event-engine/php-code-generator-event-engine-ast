<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst\Metadata\InspectioJson;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\EventMetadata;
use EventEngine\InspectioGraphCody\Event;
use EventEngine\InspectioGraphCody\JsonNode;

final class EventMetadataTest extends ResolveMetaTypeTestCase
{
    /**
     * @test
     */
    public function it_resolves_event_metadata_types(): void
    {
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'name_vo.json'));
        $this->analyzer->analyse($node);
        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building_id_vo.json'));
        $this->analyzer->analyse($node);

        $node = JsonNode::fromJson(\file_get_contents(self::FILES_DIR . 'building.json'));
        $this->analyzer->analyse($node);

        $event = $this->analyzer->connection('tF2ZuZCXsdQMhRmRXydfuW');
        $this->assertInstanceOf(Event::class, $event->identity());

        $this->assertResolvedMetadataReferencyTypes($event, EventMetadata::class);
    }
}
