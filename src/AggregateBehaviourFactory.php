<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class AggregateBehaviourFactory
{
    private Config\AggregateBehaviour $config;
    private Config\AggregateState $stateConfig;

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
        Config\AggregateState $stateConfig,
        bool $useAggregateFolder = true,
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(Config\AggregateBehaviour::withDefaultConfig(), $stateConfig);

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($self->config->getFilterClassName());
        }

        if ($basePath !== null) {
            $self->config->setBasePath($basePath);
        }

        if ($composerFile !== null) {
            $self->config->addComposerInfo($composerFile);
        }

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
            $this->stateConfig->getFilterAggregateStateClassName(),
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
