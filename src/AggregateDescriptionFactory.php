<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

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
        bool $useAggregateFolder = true,
        bool $useStoreStateIn = true,
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(Config\AggregateDescription::withDefaultConfig());

        if ($basePath !== null) {
            $self->config->setBasePath($basePath);
        }

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($self->config->getFilterClassName());
        }
        if ($useStoreStateIn) {
            $self->config->injectFilterAggregateStoreStateIn($self->config->getFilterConstValue());
        }

        if ($composerFile !== null) {
            $self->config->addComposerInfo($composerFile);
        }

        return $self;
    }

    public function codeAggregateDescription(): Code\AggregateDescription
    {
        return new Code\AggregateDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName(),
            $this->config->getFilterAggregateIdName(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterEventMethodName()
        );
    }

    public function codeClassConstant(): Code\ClassConstant
    {
        return new Code\ClassConstant(
            $this->config->getFilterConstName(),
            $this->config->getFilterConstValue()
        );
    }

    public function component(): AggregateDescription
    {
        return new AggregateDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->codeAggregateDescription(),
            $this->codeClassConstant(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterAggregateStoreStateIn()
        );
    }
}
