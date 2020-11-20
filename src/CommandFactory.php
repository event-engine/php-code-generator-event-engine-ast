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

final class CommandFactory
{
    /**
     * @var Config\Command
     **/
    private $config;

    public function __construct(Config\Command $config)
    {
        $this->config = $config;
    }

    public function config(): Config\Command
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
        $self = new self(new Config\Command());
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

    public function componentFile(): CommandFile
    {
        return new CommandFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterCommandFolder()
        );
    }

    public function componentProperty(): CommandProperty
    {
        return new CommandProperty(
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
