<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Metadata;

use EventEngine\InspectioGraph\Metadata\HasNewAggregate;
use EventEngine\InspectioGraph\Metadata\HasSchema;
use EventEngine\InspectioGraph\Metadata\Metadata;

interface CommandMetadata extends Metadata, HasSchema, HasNewAggregate
{
    public function newAggregate(): bool;

    public function schema(): ?string;
}
