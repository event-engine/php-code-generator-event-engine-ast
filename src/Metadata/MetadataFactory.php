<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata;

use EventEngine\InspectioGraph\Metadata\Metadata;
use EventEngine\InspectioGraphCody\Node;

final class MetadataFactory
{
    /**
     * @var callable
     */
    private $jsonFactory;

    public function __construct(callable $jsonFactory)
    {
        $this->jsonFactory = $jsonFactory;
    }

    public function __invoke(Node $node): Metadata
    {
        return ($this->jsonFactory)($node->metadata() ?? '{}', $node->type(), $node->name());
    }
}
