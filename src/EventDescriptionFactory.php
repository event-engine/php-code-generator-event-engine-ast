<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use OpenCodeModeling\CodeGenerator\Workflow;

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

    public function workflowComponentDescription(
        string $inputAnalyzer,
        string $inputCode,
        string $inputSchemaMetadata,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->component(),
            $output,
            $inputAnalyzer,
            $inputCode,
            $inputSchemaMetadata
        );
    }

    public function workflowComponentDescriptionMetadataSchema(
        string $inputAnalyzer,
        string $inputPathSchema,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentMetadataSchema(),
            $output,
            $inputAnalyzer,
            $inputPathSchema
        );
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
