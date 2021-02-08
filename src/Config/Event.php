<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Code\ObjectGenerator;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\VertexType;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

interface Event
{
    public function getBasePath(): string;

    public function getClassInfoList(): ClassInfoList;

    public function getFilterAggregateFolder(): ?callable;

    public function getFilterClassName(): callable;

    public function getFilterConstName(): callable;

    public function getFilterConstValue(): callable;

    public function getFilterDirectoryToNamespace(): callable;

    public function getFilterNamespaceToDirectory(): callable;

    public function getFilterPropertyName(): callable;

    public function getFilterMethodName(): callable;

    public function getParser(): Parser;

    public function getPrinter(): PrettyPrinterAbstract;

    public function getObjectGenerator(): ObjectGenerator;

    public function getValueObjectFactory(): ValueObjectFactory;

    public function determineValueObjectPath(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function determinePath(VertexType $type, EventSourcingAnalyzer $analyzer): string;
}
