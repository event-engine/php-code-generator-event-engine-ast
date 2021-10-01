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

final class QueryDescription
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
        string $resolverClassName,
        string $returnTypeDefinition,
        ?string $jsonSchemaFilename = null
    ): IdentifierGenerator {
        $namespace = $this->getCustomMetadata($document, 'ns', $this->getCustomMetadata($document, 'namespace', ''));

        $documentConstName = ($this->filterConstName)($namespace . $document->label());

        $codeSchema = 'JsonSchema::object([], [], true)';

        if ($jsonSchemaFilename) {
            $codeSchema = \sprintf(
                'JsonSchemaArray::fromFile(self::SCHEMA_PATH . \'%s\')',
                $jsonSchemaFilename,
            );
        }

        $code = \sprintf(
            <<<'EOF'
                $eventEngine->registerQuery(
                    self::%s,
                    %s
                )->resolveWith(%s::class)
                    ->setReturnType(%s);
            EOF,
            $documentConstName,
            $codeSchema,
            $resolverClassName,
            $returnTypeDefinition
        );

        return new IdentifierGenerator(
            $documentConstName,
            new BodyGenerator($this->parser, $code)
        );
    }
}
