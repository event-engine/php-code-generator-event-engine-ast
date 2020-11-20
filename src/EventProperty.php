<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Code\Metadata\JsonSchema;
use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\ReferenceType;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class EventProperty
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
    }

    public function __invoke(
        EventSourcingAnalyzer $analyzer,
        array $files
    ): array {
        foreach ($analyzer->eventMap() as $name => $event) {
            $name = $event->name();

            if (! isset($files[$name])) {
                continue;
            }
            $jsonSchema = JsonSchema::fromVertex($event);

            $type = $jsonSchema->type();

            if ($type === null) {
                continue;
            }

            $type = $type->first();

            if (! $type instanceof ObjectType) {
                throw new RuntimeException(
                    \sprintf(
                        'Need type of "%s", type "%s" given',
                        ObjectType::class,
                        \get_class($type)
                    )
                );
            }

            $properties = $type->properties();

            $eventTraverser = new NodeTraverser();

            /** @var TypeSet $propertyTypeSet */
            foreach ($properties as $typeName => $propertyTypeSet) {
                $propertyType = $propertyTypeSet->first();

                if ($propertyType instanceof ReferenceType) {
                    $propertyType = $propertyType->resolvedType()->first();
                }

                $eventTraverser->addVisitor(
                    new \OpenCodeModeling\CodeAst\NodeVisitor\Property(
                        new PropertyGenerator($typeName, $propertyType->type())
                    )
                );
            }
            $ast = $this->parser->parse($files[$name]['code']);

            $files[$name]['code'] = $this->printer->prettyPrintFile($eventTraverser->traverse($ast));
        }

        return $files;
    }
}
