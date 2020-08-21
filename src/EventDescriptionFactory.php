<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\ClassConstant;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Code\EventDescription as CodeEventDescription;
use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\Description;

final class EventDescriptionFactory
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
        string $output
    ): \OpenCodeModeling\CodeGenerator\Workflow\Description {
        return EventDescription::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->eventDescription(),
            $this->classConstant(),
            $inputAnalyzer,
            $inputCode,
            $output
        );
    }

    public function eventDescription(): CodeEventDescription
    {
        return new CodeEventDescription(
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
