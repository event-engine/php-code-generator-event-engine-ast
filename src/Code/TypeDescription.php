<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\CodeGenerator\EventEngineAst\Helper\MetadataCustomTrait;
use EventEngine\InspectioGraph\DocumentType;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Parser;

final class TypeDescription
{
    use MetadataCustomTrait;

    private Parser $parser;

    /**
     * @var callable
     **/
    private $filterConstName;

    public function __construct(
        Parser $parser,
        callable $filterConstName
    ) {
        $this->parser = $parser;
        $this->filterConstName = $filterConstName;
    }

    public function generate(
        DocumentType $document,
        ?string $jsonSchemaFilename = null
    ): IdentifierGenerator {
        $namespace = $this->getCustomMetadata($document, 'ns', $this->getCustomMetadata($document, 'namespace', ''));

        $documentConstName = ($this->filterConstName)($namespace . $document->label());

        $code = \sprintf(
            '$eventEngine->registerType(self::%s, JsonSchema::object([], [], true));',
            $documentConstName
        );

        if ($jsonSchemaFilename) {
            $code = \sprintf(
                '$eventEngine->registerType(
                        self::%s, 
                        JsonSchemaArray::fromFile(self::SCHEMA_PATH . \'%s\')
                    );',
                $documentConstName,
                $jsonSchemaFilename
            );
        }

        return new IdentifierGenerator(
            $documentConstName,
            new BodyGenerator($this->parser, $code)
        );
    }
}
