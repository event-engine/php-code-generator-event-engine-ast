<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\AggregateStateClassName;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow;

final class AggregateStateFactory
{
    /**
     * @var Config\AggregateState
     **/
    private $config;

    public function __construct(Config\AggregateState $config)
    {
        $this->config = $config;
    }

    public function config(): Config\AggregateState
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true
    ): self {
        $self = new self(new Config\AggregateState());

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
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentFile(),
            $output,
            $inputAnalyzer,
            $inputPath
        );
    }

    public function workflowComponentDescriptionModifyMethod(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentModifyMethod(),
            $output,
            $inputAnalyzer,
            $inputFiles
        );
    }

    public function workflowComponentDescriptionImmutableRecordOverride(
        string $inputAnalyzer,
        string $inputFiles,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentDescriptionImmutableRecordOverride(),
            $output,
            $inputAnalyzer,
            $inputFiles
        );
    }

    public function componentFile(): AggregateStateFile
    {
        return new AggregateStateFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder()
        );
    }

    public function componentModifyMethod(): AggregateStateModifyMethod
    {
        return new AggregateStateModifyMethod(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getFilterWithMethodName()
        );
    }

    public function componentDescriptionImmutableRecordOverride(): AggregateStateImmutableRecordOverride
    {
        return new AggregateStateImmutableRecordOverride(
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
