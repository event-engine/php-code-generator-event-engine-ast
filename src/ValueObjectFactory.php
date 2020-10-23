<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use EventEngine\CodeGenerator\Cartridge\EventEngine\Filter\ValueObjectClassName;
use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow;
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

    public function workflowComponentDescriptionFile(
        string $inputAnalyzer,
        string $inputPath,
        string $output
    ): Workflow\Description {
        return new Workflow\ComponentDescriptionWithSlot(
            $this->componentFile(),
            $output,
            $inputAnalyzer,
            $inputPath
        );
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
            new AstValueObjectFactory($this->config->getParser(), $typed),
            $this->config->getFilterClassName(),
            $this->config->getFilterValueObjectFolder()
        );
    }
}
