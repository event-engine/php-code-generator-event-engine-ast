<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\Metadata\JsonSchema;
use EventEngine\CodeGenerator\EventEngineAst\Code\ObjectGenerator;
use EventEngine\InspectioGraph\AggregateConnection;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;

final class ValueObjectFile
{
    /**
     * @var callable
     **/
    private $filterValueObjectPath;

    private ObjectGenerator $objectGenerator;

    public function __construct(
        ObjectGenerator $objectGenerator,
        ?callable $filterValueObjectPath
    ) {
        $this->objectGenerator = $objectGenerator;
        $this->filterValueObjectPath = $filterValueObjectPath;
    }

    /**
     * @param EventSourcingAnalyzer $analyzer
     * @param string $path
     * @return array Assoc array with ValueObject name and file content
     */
    public function __invoke(EventSourcingAnalyzer $analyzer, string $path): array
    {
        $files = [];

        /** @var AggregateConnection $aggregateConnection */
        foreach ($analyzer->aggregateMap() as $name => $aggregateConnection) {
            $pathValueObject = $path;

            if ($this->filterValueObjectPath !== null) {
                $pathValueObject .= DIRECTORY_SEPARATOR . ($this->filterValueObjectPath)($aggregateConnection->aggregate()->label());
            }

            foreach ($aggregateConnection->commandMap() as $command) {
                $jsonSchema = JsonSchema::fromVertex($command);

                $type = $jsonSchema->type();

                if ($type === null) {
                    continue;
                }
                $fileCollection = $this->objectGenerator->generateValueObject($pathValueObject, 'PleaseRemoveMe', $type);

                $files = \array_merge($files, $this->objectGenerator->generateFiles($fileCollection));
            }
        }

        return $files;
    }
}
