<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

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
     * @param bool $useAggregateFolder Indicates if the event folder with events should be generated under the aggregate name
     * @param bool $useEventFolder Indicates if each event should be generated in it's own folder depending on event name
     * @param string|null $basePath
     * @param string|null $composerFile
     * @return EventFactory
     */
    public static function withDefaultConfig(
        bool $useAggregateFolder = true,
        bool $useEventFolder = false,
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(Config\Event::withDefaultConfig());

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($self->config->getFilterClassName());
        }

        if ($useEventFolder) {
            $self->config->setFilterEventFolder($self->config->getFilterClassName());
        }

        if ($basePath !== null) {
            $self->config->setBasePath($basePath);
        }

        if ($composerFile !== null) {
            $self->config->addComposerInfo($composerFile);
        }

        return $self;
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

    public function componentProperty(): EventProperty
    {
        return new EventProperty(
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
