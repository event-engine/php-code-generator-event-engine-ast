<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeGenerator\Workflow\ComponentDescriptionWithSlot;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

final class CommandDescriptionMetadataSchema
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

        foreach ($analyzer->commandMap() as $name => $commandVertex) {
            $metadata = $commandVertex->metadataInstance();

            if ($metadata === null) {
                continue;
            }
            $schema = $metadata->schema();

            if ($schema === null) {
                continue;
            }

            $files[$name] = [
                'filename' => $pathSchema . ($this->filterConstName)($commandVertex->label()) . '.json',
                'code' => $schema,
            ];
        }

        return $files;
    }

    public static function workflowComponentDescription(
        callable $filterConstName,
        string $inputAnalyzer,
        string $inputPathSchema,
        string $output
    ): Description {
        $instance = new self($filterConstName);

        return new ComponentDescriptionWithSlot(
            $instance,
            $output,
            $inputAnalyzer,
            $inputPathSchema
        );
    }
}
