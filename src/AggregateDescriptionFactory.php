<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Filter\Id;
use EventEngine\CodeGenerator\EventEngineAst\Filter\StateName;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;

final class AggregateDescriptionFactory
{
    /**
     * @var Config\AggregateDescription
     **/
    private $config;

    public function __construct(Config\AggregateDescription $config)
    {
        $this->config = $config;
    }

    public function config(): Config\AggregateDescription
    {
        return $this->config;
    }

    public static function withDefaultConfig(
        callable $filterConstName,
        callable $filterConstValue,
        callable $filterDirectoryToNamespace,
        bool $useAggregateFolder = true,
        bool $useStoreStateIn = true
    ): self {
        $self = new self(new Config\AggregateDescription());
        $self->config->setFilterConstName($filterConstName);
        $self->config->setFilterConstValue($filterConstValue);
        $self->config->setFilterDirectoryToNamespace($filterDirectoryToNamespace);
        $self->config->setFilterAggregateIdName(new Id($filterConstValue));

        if ($useAggregateFolder) {
            $self->config->setFilterAggregateFolder($filterConstValue);
        }
        if ($useStoreStateIn) {
            $self->config->setFilterAggregateStoreStateIn(new StateName($filterConstValue));
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

    public function codeAggregateDescription(): Code\AggregateDescription
    {
        return new Code\AggregateDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName(),
            $this->config->getFilterAggregateIdName(),
            $this->config->getFilterCommandMethodName(),
            $this->config->getFilterEventMethodName()
        );
    }

    public function codeClassConstant(): Code\ClassConstant
    {
        return new Code\ClassConstant(
            $this->config->getFilterConstName(),
            $this->config->getFilterConstValue()
        );
    }

    public function component(): AggregateDescription
    {
        return new AggregateDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->config->getClassInfoList(),
            $this->codeAggregateDescription(),
            $this->codeClassConstant(),
            $this->config->getFilterClassName(),
            $this->config->getFilterAggregateFolder(),
            $this->config->getFilterAggregateStoreStateIn()
        );
    }
}
