<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\RuntimeException;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\DocumentMetadata;
use EventEngine\CodeGenerator\EventEngineAst\Metadata\HasTypeSet;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use OpenCodeModeling\CodeAst\Builder\FileCollection;

final class ValueObject
{
    use MetadataTypeSetTrait;

    private Naming $config;

    public function __construct(Naming $config)
    {
        $this->config = $config;
    }

    public function generate(
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        foreach ($analyzer->documentMap() as $name => $document) {
            $documentMetadata = $document->metadataInstance();

            if (
                ! $documentMetadata instanceof DocumentMetadata
                && ! $documentMetadata instanceof HasTypeSet
            ) {
                throw new RuntimeException(
                    \sprintf(
                        'Cannot generate value object "%s". Need metadata of type "%s"',
                        $document->name(),
                        DocumentMetadata::class
                    )
                );
            }
            $documentFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);
            $jsonSchemaTypeSet = $documentMetadata->typeSet();

            $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
                $documentFqcn,
                $this->config->config()->determineValueObjectPath($document, $analyzer),
                $this->config->config()->determineValueObjectSharedPath(),
                $jsonSchemaTypeSet
            );

            foreach ($files as $item) {
                $fileCollection->add($item);
            }
        }
    }
}
