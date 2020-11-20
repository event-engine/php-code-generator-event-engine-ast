<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;

final class AggregateBehaviourFactory
{
    /**
     * @var Config\AggregateBehaviour
     **/
    private $config;

    /**
     * @var Config\AggregateState
     **/
    private $stateConfig;

    public function __construct(Config\AggregateBehaviour $config, Config\AggregateState $stateConfig)
    {
        $this->config = $config;
        $this->stateConfig = $stateConfig;
    }

    public function config(): Config\AggregateBehaviour
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        Config\AggregateState $stateConfig,
        bool $useAggregateFolder = true
    ): self {
        $self = new self(new Config\AggregateBehaviour(), $stateConfig);
        $self->config->setFilterConstName($filterConstName);
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

    public function codeCommandMethod(): Code\AggregateBehaviourCommandMethod
    {
        return new Code\AggregateBehaviourCommandMethod(
            $this->config->getParser(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterParameterName()
        );
    }

    public function codeEventMethod(): Code\AggregateBehaviourEventMethod
    {
        return new Code\AggregateBehaviourEventMethod(
            $this->config->getParser(),
            $this->config->getFilterEventMethodName(),
            $this->config->getFilterParameterName()
        );
    }

    public function componentFile(): AggregateBehaviourFile
    {
        return new AggregateBehaviourFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->stateConfig->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->stateConfig->getFilterAggregateFolder()
        );
    }

    public function componentEventMethod(): AggregateBehaviourEventMethod
    {
        return new AggregateBehaviourEventMethod(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->codeEventMethod()
        );
    }

    public function componentCommandMethod(): AggregateBehaviourCommandMethod
    {
        return new AggregateBehaviourCommandMethod(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->codeCommandMethod()
        );
    }
}
