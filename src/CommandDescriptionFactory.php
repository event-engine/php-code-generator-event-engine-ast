<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\CommandDescription as CodeCommandDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\Description;

final class CommandDescriptionFactory
{
    /**
     * @var Description
     **/
    private $config;

    public function __construct(Description $config)
    {
        $this->config = $config;
    }

    public function config(): Description
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue
    ): self {
        $self = new self(new Description());

        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);

        return $self;
    }

    public function workflowComponentDescription(
        string $inputAnalyzer,
        string $inputCode,
        string $inputSchemaMetadata,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return CommandDescription::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->commandDescription(),
            $this->classConstant(),
            $inputAnalyzer,
            $inputCode,
            $inputSchemaMetadata,
            $output
        );
    }

    public function workflowComponentDescriptionMetadataSchema(
        string $inputAnalyzer,
        string $inputPathSchema,
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return CommandDescriptionMetadataSchema::workflowComponentDescription(
            $this->config->getFilterConstName(),
            $inputAnalyzer,
            $inputPathSchema,
            $output
        );
    }

    public function commandDescription(): CodeCommandDescription
    {
        return new CodeCommandDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName()
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
