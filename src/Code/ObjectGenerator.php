<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use EventEngine\CodeGenerator\EventEngineAst\Config\Base;
use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\PhpFile;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;

final class ObjectGenerator
{
    private Base $config;

    public function __construct(Base $config)
    {
        $this->config = $config;
    }

    public function sortThings(FileCollection $fileCollection): void
    {
        $sort = static function (string $a, string $b) {
            return $a <=> $b;
        };

        foreach ($fileCollection as $classBuilder) {
            if ($classBuilder instanceof ClassBuilder) {
                $classBuilder->sortTraits($sort);
            }
            if ($classBuilder instanceof PhpFile) {
                $classBuilder->sortNamespaceImports($sort);
            }
        }
    }

    public function generateFile(File $classBuilder): array
    {
        return $this->generateFiles(FileCollection::fromItems($classBuilder))[0];
    }

    public function generateFiles(FileCollection $fileCollection, callable $applyCodeStyle = null): array
    {
        if ($applyCodeStyle === null) {
            $applyCodeStyle = static fn (string $code) => $code;
        }

        $files = [];

        $this->sortThings($fileCollection);

        foreach ($this->config->getValueObjectFactory()->generateFiles($fileCollection) as $filename => $code) {
            $files[$filename] = [
                'filename' => $filename,
                'code' => ($applyCodeStyle)($code),
            ];
        }

        return $files;
    }

    public function addClassConstantsForProperties(FileCollection $fileCollection, int $visibility): void
    {
        $this->config->getValueObjectFactory()->addClassConstantsForProperties(
            $fileCollection,
            $visibility
        );
    }

    public function addGetterMethodsForProperties(FileCollection $fileCollection): void
    {
        $this->config->getValueObjectFactory()->addGetterMethodsForProperties(
            $fileCollection,
            true
        );
    }

    public function addImmutableRecordLogic(FileCollection $fileCollection): void
    {
        foreach ($fileCollection as $file) {
            if (! $file instanceof ClassBuilder
                || $this->skipImmutableRecord($file)
            ) {
                continue;
            }
            $this->codeImmutableRecordLogic($file);
        }
    }

    private function skipImmutableRecord(ClassBuilder $classBuilder): bool
    {
        return $classBuilder->hasMethod('fromItems')
            || $classBuilder->hasMethod('toString')
            || $classBuilder->hasMethod('toInt')
            || $classBuilder->hasMethod('toFloat')
            || $classBuilder->hasMethod('toBool')
            || \strpos($classBuilder->getFqcn(), 'Exception') !== false;
    }

    /**
     * @param string $fqcn
     * @param string $valueObjectFolder
     * @param string $sharedValueObjectFolder
     * @param TypeSet|null $jsonSchemaSet
     * @return FileCollection
     */
    public function generateObject(
        string $fqcn,
        string $valueObjectFolder,
        string $sharedValueObjectFolder,
        TypeSet $jsonSchemaSet = null
    ): FileCollection {
        $classInfo = $this->config->getClassInfoList()->classInfoForNamespace($fqcn);
        $classNamespace = $classInfo->getClassNamespace($fqcn);

        $objectClassBuilder = ClassBuilder::fromScratch(
            $classInfo->getClassName($fqcn),
            $classNamespace
        )->setFinal(true);

        $fileCollection = FileCollection::emptyList();
        $fileCollection->add($objectClassBuilder);

        if ($jsonSchemaSet !== null) {
            $this->config->getValueObjectFactory()->generateClasses(
                $objectClassBuilder,
                $fileCollection,
                $jsonSchemaSet,
                $valueObjectFolder,
                null,
                $sharedValueObjectFolder
            );
        }

        $this->addGetterMethodsForProperties($fileCollection);

        return $fileCollection;
    }

    /**
     * Generates an immutable record class e. g. command, event or aggregate state and corresponding value objects
     * from the JSON metadata
     *
     * @param string $fqcn Immutable record full qualified class name
     * @param string $valueObjectDirectory Path to store the corresponding value objects
     * @param string $sharedValueObjectFolder Path to store shared value objects
     * @param TypeSet|null $jsonSchemaSet JSON schema from which value objects will be generated
     * @return FileCollection Contains all generated classes
     */
    public function generateImmutableRecord(
        string $fqcn,
        string $valueObjectDirectory,
        string $sharedValueObjectFolder,
        TypeSet $jsonSchemaSet = null
    ): FileCollection {
        $fileCollection = $this->generateObject(
            $fqcn,
            $valueObjectDirectory,
            $sharedValueObjectFolder,
            $jsonSchemaSet
        );

        $this->addClassConstantsForProperties($fileCollection, ClassConstGenerator::FLAG_PUBLIC);

        $this->addImmutableRecordLogic($fileCollection);

        return $fileCollection;
    }

    private function codeImmutableRecordLogic(ClassBuilder $file): void
    {
        $file->addNamespaceImport(
            'EventEngine\Data\ImmutableRecord',
            'EventEngine\Data\ImmutableRecordLogic',
        );
        $file->addImplement('ImmutableRecord')
            ->addTrait('ImmutableRecordLogic');
    }
}
