<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\AggregateState;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\AggregateStateClassName;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

final class AggregateStateFactory
{
    /**
     * @var AggregateState
     **/
    private $config;

    public function __construct(AggregateState $config)
    {
        $this->config = $config;
    }

    public function config(): AggregateState
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true
    ): self {
        $self = new self(new AggregateState());

        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);
        $self->config->setFilterClassName(new AggregateStateClassName($self->config->getFilterClassName()));

        if (true === $useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
        }
        $autoloadFile = 'vendor/autoload.php';

        $classInfoList = new ClassInfoList();

        if (\file_exists($autoloadFile) && \is_readable($autoloadFile)) {
            $classInfoList->addClassInfo(
                ...Psr4Info::fromComposer(
                    require $autoloadFile,
                    $self->config->getFilterDirectoryToNamespace(),
                    $self->config->getFilterNamespaceToDirectory()
                )
            );
        }

        $self->config->setClassInfoList($classInfoList);

        return $self;
    }

    public function workflowComponentDescriptionFile(
        string $inputAnalyzer,
        string $inputPath,
        string $output
    ): Description {
        return AggregateStateFile::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $inputAnalyzer,
            $inputPath,
            $output
        );
    }

    public function workflowComponentDescriptionModifyMethod(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Description {
        return AggregateStateModifyMethod::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getFilterWithMethodName(),
            $inputAnalyzer,
            $inputFiles,
            $output
        );
    }

    public function workflowComponentDescriptionImmutableRecordOverride(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Description {
        return AggregateStateImmutableRecordOverride::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $inputAnalyzer,
            $inputFiles,
            $output
        );
    }
}
