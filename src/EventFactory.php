<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow;

final class EventFactory
{
    /**
     * @var Config\Event
     **/
    private $config;

    public function __construct(Config\Event $config)
    {
        $this->config = $config;
    }

    public function config(): Config\Event
    {
        return $this->config;
    }

    /**
     * @param callable $filterConstName
     * @param callable $filterConstValue
     * @param callable $filterDirectoryToNamespace
     * @param bool $useAggregateFolder Indicates if the event folder with events should be generated under the aggregate name
     * @param bool $useEventFolder Indicates if each event should be generated in it's own folder depending on event name
     * @return EventFactory
     */
    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true,
        bool $useEventFolder = false
    ): self {
        $self = new self(new Config\Event());
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
        }
        if ($useEventFolder) {
            $self->config->setFilterEventFolder($filterConstValue);
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
        string $inputEventPath,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentFile(),
            $output,
            $inputAnalyzer,
            $inputEventPath
        );
    }

    public function componentFile(): EventFile
    {
        return new EventFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterEventFolder()
        );
    }
}
