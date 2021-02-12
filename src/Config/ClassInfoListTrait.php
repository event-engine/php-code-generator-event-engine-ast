<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use EventEngine\CodeGenerator\EventEngineAst\Exception\LogicException;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;

trait ClassInfoListTrait
{
    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    public function getClassInfoList(): ClassInfoList
    {
        if (null === $this->classInfoList) {
            $this->classInfoList = new ClassInfoList();
        }

        return $this->classInfoList;
    }

    public function setClassInfoList(ClassInfoList $classInfoList): void
    {
        $this->classInfoList = $classInfoList;
    }

    public function addComposerInfo(string $composerFile): void
    {
        if (! \file_exists($composerFile) || ! \is_readable($composerFile)) {
            throw new LogicException(\sprintf('Composer file "%s" does not exists or is not readable.', $composerFile));
        }

        $this->getClassInfoList()->addClassInfo(
            ...Psr4Info::fromComposer(
                $this->getBasePath(),
                \file_get_contents($composerFile),
                $this->getFilterDirectoryToNamespace(),
                $this->getFilterNamespaceToDirectory()
            )
        );
    }

    abstract public function getBasePath(): string;

    abstract public function getFilterDirectoryToNamespace(): callable;

    abstract public function getFilterNamespaceToDirectory(): callable;
}
