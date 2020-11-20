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
        callable $filterDirectoryToNamespace
    ): self {
        $self = new self(new Config\EmptyClass());
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);

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

    public function component(): EmptyClass
    {
        return new EmptyClass(
            $this->config->getClassInfoList(),
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
