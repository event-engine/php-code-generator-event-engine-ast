<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Exception\WrongVertexConnection;
use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataTypeSetTrait;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexConnection;
use EventEngine\InspectioGraph\VertexType;
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
        VertexConnection $connection,
        EventSourcingAnalyzer $analyzer,
        FileCollection $fileCollection
    ): void {
        if ($connection->identity()->type() !== VertexType::TYPE_DOCUMENT) {
            throw WrongVertexConnection::forConnection($connection, VertexType::TYPE_DOCUMENT);
        }

        /** @var DocumentType $document */
        $document = $connection->identity();

        $documentFqcn = $this->config->getFullyQualifiedClassName($document, $analyzer);

        $files = $this->config->config()->getObjectGenerator()->generateImmutableRecord(
            $documentFqcn,
            $this->config->config()->determineValueObjectPath($document, $analyzer),
            $this->config->config()->determineValueObjectSharedPath(),
            $this->getMetadataTypeSetFromVertex($document)
        );

        foreach ($files as $item) {
            $fileCollection->add($item);
        }
    }
}
