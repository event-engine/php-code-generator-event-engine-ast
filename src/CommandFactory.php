<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Config\Command;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

final class CommandFactory
{
    /**
     * @var Command
     **/
    private $config;

    public function __construct(Command $config)
    {
        $this->config = $config;
    }

    public function config(): Command
    {
        return $this->config;
    }

    /**
     * @param callable $filterConstName
     * @param callable $filterConstValue
     * @param callable $filterDirectoryToNamespace
     * @param bool $useAggregateFolder Indicates if the command folder with commands should be generated under the aggregate name
     * @param bool $useCommandFolder Indicates if each command should be generated in it's own folder depending on command name
     * @return CommandFactory
     */
    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true,
        bool $useCommandFolder = false
    ): self {
        $self = new self(new Command());
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
        }
        if ($useCommandFolder) {
            $self->config->setFilterCommandFolder($filterConstValue);
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
        string $inputCommandPath,
        string $output
    ): Description {
        return CommandFile::workflowComponentDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterCommandFolder(),
            $inputAnalyzer,
            $inputCommandPath,
            $output
        );
    }
}
