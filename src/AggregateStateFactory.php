<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Filter\AggregateStateClassName;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;

final class AggregateStateFactory
{
    /**
     * @var Config\AggregateState
     **/
    private $config;

    public function __construct(Config\AggregateState $config)
    {
        $this->config = $config;
    }

    public function config(): Config\AggregateState
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true
    ): self {
        $self = new self(new Config\AggregateState());

        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);
        $self->config->setFilterClassName(new AggregateStateClassName($self->config->getFilterClassName()));

        if (true === $useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
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

    public function componentFile(): AggregateStateFile
    {
        return new AggregateStateFile(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder()
        );
    }

    public function componentModifyMethod(): AggregateStateModifyMethod
    {
        return new AggregateStateModifyMethod(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getFilterWithMethodName()
        );
    }

    public function componentDescriptionImmutableRecordOverride(): AggregateStateImmutableRecordOverride
    {
        return new AggregateStateImmutableRecordOverride(
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
