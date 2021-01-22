<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class AggregateStateFactory
{
    public const STATE_SUFFIX = '_STATE';

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
        bool $useAggregateFolder = true,
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(Config\AggregateState::withDefaultConfig());

        if (true === $useAggregateFolder) {
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

    public function componentFile(): AggregateStateFile
    {
        return new AggregateStateFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterAggregateStateClassName(),
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
