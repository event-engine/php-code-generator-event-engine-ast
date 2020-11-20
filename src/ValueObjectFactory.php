<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Filter\ValueObjectClassName;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory as AstValueObjectFactory;

final class ValueObjectFactory
{
    /**
     * @var Config\ValueObject
     **/
    private $config;

    public function __construct(Config\ValueObject $config)
    {
        $this->config = $config;
    }

    public function config(): Config\ValueObject
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useValueObjectFolder = true
    ): self {
        $self = new self(new Config\ValueObject());

        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);
        $self->config->setFilterClassName(new ValueObjectClassName($self->config->getFilterClassName()));

        if (true === $useValueObjectFolder) {
            $self->config->setFilterValueObjectFolder($filterConstValue);
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

    public function componentFile(): ValueObjectFile
    {
        $typed = false;

        if (\version_compare(\phpversion(), '7.4.0', '>=')) {
            $typed = true;
        }

        return new ValueObjectFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            new AstValueObjectFactory(
                $this->config->getParser(),
                $typed,
                $this->config->getFilterConstName(),
                $this->config->getFilterConstValue()
            ),
            $this->config->getFilterClassName(),
            $this->config->getFilterValueObjectFolder()
        );
    }
}
