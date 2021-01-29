<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class CommandFactory
{
    /**
     * @var Config\PreConfiguredCommand
     **/
    private $config;

    public function __construct(Config\PreConfiguredCommand $config)
    {
        $this->config = $config;
    }

    public function config(): Config\PreConfiguredCommand
    {
        return $this->config;
    }

    /**
     * @param bool $useAggregateFolder Indicates if the command folder with commands should be generated under the aggregate name
     * @param bool $useCommandFolder Indicates if each command should be generated in it's own folder depending on command name
     * @param string|null $basePath
     * @param string|null $composerFile
     * @return CommandFactory
     */
    public static function withDefaultConfig(
        bool $useAggregateFolder = true,
        bool $useCommandFolder = false,
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(new Config\PreConfiguredCommand());

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($self->config->getFilterClassName());
        }
        if ($useCommandFolder) {
            $self->config->setFilterCommandFolder($self->config->getFilterClassName());
        }

        if ($basePath !== null) {
            $self->config->setBasePath($basePath);
        }

        if ($composerFile !== null) {
            $self->config->addComposerInfo($composerFile);
        }

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
