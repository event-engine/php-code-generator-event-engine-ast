<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\InspectioGraph\AggregateType;
use EventEngine\InspectioGraph\CommandType;
use EventEngine\InspectioGraph\DocumentType;
use EventEngine\InspectioGraph\EventSourcingAnalyzer;
use EventEngine\InspectioGraph\EventType;
use EventEngine\InspectioGraph\VertexType;

interface Naming
{
    public function getContextName(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function getApiDescriptionFullyQualifiedClassName(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function getApiQueryDescriptionFullyQualifiedClassName(DocumentType $type, EventSourcingAnalyzer $analyzer): string;

    public function getAggregateStateFullyQualifiedClassName(AggregateType $type, EventSourcingAnalyzer $analyzer): string;

    public function getAggregateBehaviourFullyQualifiedClassName(AggregateType $type, EventSourcingAnalyzer $analyzer): string;

    public function getAggregateIdFullyQualifiedClassName(AggregateType $type, EventSourcingAnalyzer $analyzer): string;

    public function getQueryFullyQualifiedClassName(DocumentType $type, EventSourcingAnalyzer $analyzer): string;

    public function getResolverFullyQualifiedClassName(DocumentType $type, EventSourcingAnalyzer $analyzer): string;

    public function getFinderFullyQualifiedClassName(DocumentType $type, EventSourcingAnalyzer $analyzer): string;

    public function getCollectionFullyQualifiedClassName(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function getClassNameFromFullyQualifiedClassName(string $fqcn): string;

    public function getClassNamespaceFromFullyQualifiedClassName(string $fqcn): string;

    public function getFullPathFromFullyQualifiedClassName(string $fqcn): string;

    public function getFullyQualifiedClassName(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function getMessageName(VertexType $type, EventSourcingAnalyzer $analyzer): string;

    public function getClassNamespaceFromPath(string $path): string;

    public function getFullyQualifiedClassNameFromFilename(string $filename): string;

    public function getAggregateBehaviourCommandHandlingMethodName(CommandType $type, EventSourcingAnalyzer $analyzer): string;

    public function getAggregateBehaviourEventHandlingMethodName(EventType $type, EventSourcingAnalyzer $analyzer): string;

    public function config(): Base;

    public function getFilterAggregateIdName(): callable;

    public function getFilterCommandMethodName(): callable;

    public function getFilterEventMethodName(): callable;

    public function getFilterWithMethodName(): callable;
}
