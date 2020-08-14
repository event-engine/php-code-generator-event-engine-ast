<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\AggregateBehaviourCommandMethod  as CodeCommandMethod;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\AggregateBehaviourEventMethod as CodeEventMethod;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\AggregateBehaviour;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\AggregateState;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

class AggregateBehaviourFactory
{
    /**
     * @var AggregateBehaviour
     **/
    private $config;

    /**
     * @var AggregateState
     **/
    private $stateConfig;

    public function __construct(AggregateBehaviour $config, AggregateState $stateConfig)
    {
        $this->config = $config;
        $this->stateConfig = $stateConfig;
    }

    public function config(): AggregateBehaviour
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        AggregateState $stateConfig,
        bool $useAggregateFolder = true
    ): self {
        $self = new self(new AggregateBehaviour(), $stateConfig);
        $self->config->setFilterDirectoryToNamespace($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);

        if ($useAggregateFolder) {
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

    public function commandMethod(): Code\AggregateBehaviourCommandMethod
    {
        return new CodeCommandMethod(
            $this->config->getParser(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterParameterName()
        );
    }

    public function eventMethod(): Code\AggregateBehaviourEventMethod
    {
        return new CodeEventMethod(
            $this->config->getParser(),
            $this->config->getFilterEventMethodName(),
            $this->config->getFilterParameterName()
        );
    }

    public function workflowComponentDescriptionFile(
        string $inputAnalyzer,
        string $inputAggregatePath,
        string $inputAggregateStatePath,
        string $inputApiEventFilename,
        string $output
    ): Description {
        return AggregateBehaviourFile::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->stateConfig->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->stateConfig->getFilterAggregateFolder(),
            $inputAnalyzer,
            $inputAggregatePath,
            $inputAggregateStatePath,
            $inputApiEventFilename,
            $output
        );
    }

    public function workflowComponentDescriptionEventMethod(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Description {
        return AggregateBehaviourEventMethod::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->eventMethod(),
            $inputAnalyzer,
            $inputFiles,
            $output
        );
    }

    public function workflowComponentDescriptionCommandMethod(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Description {
        return AggregateBehaviourCommandMethod::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->commandMethod(),
            $inputAnalyzer,
            $inputFiles,
            $output
        );
    }
}
