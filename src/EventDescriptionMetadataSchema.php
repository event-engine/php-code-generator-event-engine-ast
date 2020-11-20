<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;

final class EventDescriptionMetadataSchema
{
    /**
     * @var callable
     **/
    private $filterConstName;

    public function __construct(callable $filterConstName)
    {
        $this->filterConstName = $filterConstName;
    }

    public function __invoke(EventSourcingAnalyzer $analyzer, string $pathSchema): array
    {
        $files = [];

        $pathSchema = \rtrim(\rtrim($pathSchema), '\/\\') . DIRECTORY_SEPARATOR;

        foreach ($analyzer->eventMap() as $name => $eventVertex) {
            $metadata = $eventVertex->metadataInstance();

            if ($metadata === null) {
                continue;
            }
            $schema = $metadata->schema();

            if ($schema === null) {
                continue;
            }

            $files[$name] = [
                'filename' => $pathSchema . ($this->filterConstName)($eventVertex->label()) . '.json',
                'code' => $schema,
            ];
        }

        return $files;
    }
}
