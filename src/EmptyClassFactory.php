<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine;

use OpenCodeModeling\CodeGenerator\Code\ClassInfoList;
use OpenCodeModeling\CodeGenerator\Code\Psr4Info;
use OpenCodeModeling\CodeGenerator\Workflow\Description;

class EmptyClassFactory
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

    public static function withDefaultConfig(): self
    {
        $self = new self(new Config\EmptyClass());

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

    public function workflowComponentDescription(
        string $inputFilename,
        string $output
    ): Description {
        return EmptyClass::workflowComponentDescription(
            $this->config->getClassInfoList(),
            $this->config->getParser(),
            $this->config->getPrinter(),
            $inputFilename,
            $output
        );
    }
}
