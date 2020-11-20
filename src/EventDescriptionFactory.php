<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class EventDescriptionFactory
{
    /**
     * @var Config\EventDescription
     **/
    private $config;

    public function __construct(Config\EventDescription $config)
    {
        $this->config = $config;
    }

    public function config(): Config\EventDescription
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue
    ): self {
        $self = new self(new Config\EventDescription());

        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);

        return $self;
    }

    public function codeEventDescription(): Code\EventDescription
    {
        return new Code\EventDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName()
        );
    }

    public function codeClassConstant(): Code\ClassConstant
    {
        return new Code\ClassConstant(
            $this->config->getFilterConstName(),
            $this->config->getFilterConstValue()
        );
    }

    public function component(): EventDescription
    {
        return new EventDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->codeEventDescription(),
            $this->codeClassConstant(),
        );
    }

    public function componentMetadataSchema(): EventDescriptionMetadataSchema
    {
        return new EventDescriptionMetadataSchema(
            $this->config->getFilterConstName()
        );
    }
}
