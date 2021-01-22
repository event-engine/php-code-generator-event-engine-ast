<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class EmptyClassFactory
{
    /**
     * @var Config\EmptyClass
     **/
    private $config;

    public function __construct(Config\EmptyClass $config)
    {
        $this->config = $config;
    }

    public function config(): Config\EmptyClass
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        ?string $basePath = null,
        ?string $composerFile = null
    ): self {
        $self = new self(Config\EmptyClass::withDefaultConfig());

        if ($basePath !== null) {
            $self->config->setBasePath($basePath);
        }

        if ($composerFile !== null) {
            $self->config->addComposerInfo($composerFile);
        }

        return $self;
    }

    public function component(): EmptyClass
    {
        return new EmptyClass(
            $this->config->getClassInfoList(),
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
