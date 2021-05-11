<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\InspectioGraph\Metadata;

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata;

use EventEngine\InspectioGraph\Metadata\HasStreams;
use EventEngine\InspectioGraph\Metadata\Metadata;

interface PolicyMetadata extends Metadata, HasStreams
{
    public function streams(): array;
}
