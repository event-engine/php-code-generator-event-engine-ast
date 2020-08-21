<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\AggregateDescription as CodeAggregateDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\Id;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\StateName;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

final class AggregateDescriptionFactory
{
    /**
     * @var Config\AggregateDescription
     **/
    private $config;

    public function __construct(Config\AggregateDescription $config)
    {
        $this->config = $config;
    }

    public function config(): Config\AggregateDescription
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true,
        bool $useStoreStateIn = true
    ): self {
        $self = new self(new Config\AggregateDescription());
        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);
        $self->config->setFilterAggregateIdName(new Id($filterConstValue));

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
        }
        if ($useStoreStateIn) {
            $self->config->setFilterAggregateStoreStateIn(new StateName($filterConstValue));
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

    public function workflowComponentDescription(
        string $inputAnalyzer,
        string $inputCode,
        string $inputAggregatePath,
        string $output
    ): Description {
        return AggregateDescription::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->aggregateDescription(),
            $this->classConstant(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterAggregateStoreStateIn(),
            $inputAnalyzer,
            $inputCode,
            $inputAggregatePath,
            $output
        );
    }

    public function aggregateDescription(): Code\AggregateDescription
    {
        return new CodeAggregateDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName(),
            $this->config->getFilterAggregateIdName(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterEventMethodName()
        );
    }

    public function classConstant(): ClassConstant
    {
        return new ClassConstant(
            $this->config->getFilterConstName(),
            $this->config->getFilterConstValue()
        );
    }
}
