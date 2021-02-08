<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Code;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use OpenCodeModeling\JsonSchemaToPhpAst\ValueObjectFactory;

final class ObjectGenerator
{
    /**
     * @var callable
     */
    private $classNameFilter;

    private ClassInfoList $classInfoList;

    private ValueObjectFactory $valueObjectFactory;

    public function __construct(
        ClassInfoList $classInfoList,
        ValueObjectFactory $valueObjectFactory,
        callable $classNameFilter
    ) {
        $this->classInfoList = $classInfoList;
        $this->valueObjectFactory = $valueObjectFactory;
        $this->classNameFilter = $classNameFilter;
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
            $classBuilder->sortNamespaceImports($sort);
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

        foreach ($this->valueObjectFactory->generateFiles($fileCollection) as $filename => $code) {
            $files[$filename] = [
                'filename' => $filename,
                'code' => ($applyCodeStyle)($code),
            ];
        }

        return $files;
    }

    public function addClassConstantsForProperties(FileCollection $fileCollection, int $visibility): void
    {
        $this->valueObjectFactory->addClassConstantsForProperties(
            $fileCollection,
            $visibility
        );
    }

    public function addGetterMethodsForProperties(FileCollection $fileCollection): void
    {
        $this->valueObjectFactory->addGetterMethodsForProperties(
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
            || $classBuilder->hasMethod('toBool');
    }

    public function generateValueObject(
        string $valueObjectDirectory,
        string $className,
        TypeSet $jsonSchemaSet
    ): FileCollection {
        $className = ($this->classNameFilter)($className);
        $classInfo = $this->classInfoList->classInfoForPath($valueObjectDirectory);

        $classBuilder = ClassBuilder::fromScratch(
            $className,
            $classInfo->getClassNamespaceFromPath($valueObjectDirectory)
        );

        $fileCollection = FileCollection::emptyList();

        $this->valueObjectFactory->generateClasses(
            $classBuilder,
            $fileCollection,
            $jsonSchemaSet,
            $valueObjectDirectory
        );

        return $fileCollection;
    }

    /**
     * Generates only value objects of given JSON schema. The object itself is not generated.
     *
     * @param string $valueObjectDirectory
     * @param TypeSet $jsonSchemaSet
     * @return FileCollection
     */
    public function generateValueObjectsFromObjectProperties(
        string $valueObjectDirectory,
        TypeSet $jsonSchemaSet
    ): FileCollection {
        $classBuilder = ClassBuilder::fromScratch(
            'Please_Remove_Me',
        );

        $fileCollection = FileCollection::emptyList();

        $this->valueObjectFactory->generateClasses(
            $classBuilder,
            $fileCollection,
            $jsonSchemaSet,
            $valueObjectDirectory
        );

        return $fileCollection->remove($classBuilder);
    }

    /**
     * Generates an immutable record class e. g. command, event or aggregate state and corresponding value objects
     * from the JSON metadata
     *
     * @param string $immutableRecordClassName Immutable record class name
     * @param string $immutableRecordDirectory Path to store the immutable record class
     * @param string $valueObjectDirectory Path to store the corresponding value objects
     * @param TypeSet|null $jsonSchemaSet JSON schema from which value objects will be generated
     * @return FileCollection Contains all generated classes
     */
    public function generateImmutableRecord(
        string $immutableRecordClassName,
        string $immutableRecordDirectory,
        string $valueObjectDirectory,
        TypeSet $jsonSchemaSet = null
    ): FileCollection {
        $classInfo = $this->classInfoList->classInfoForPath($immutableRecordDirectory);

        $classBuilder = ClassBuilder::fromScratch(
                ($this->classNameFilter)($immutableRecordClassName),
                $classInfo->getClassNamespaceFromPath($immutableRecordDirectory)
            )
            ->setFinal(true);

        $this->codeImmutableRecordLogic($classBuilder);

        $fileCollection = FileCollection::fromItems($classBuilder);

        if ($jsonSchemaSet !== null) {
            $this->valueObjectFactory->generateClasses(
                $classBuilder,
                $fileCollection,
                $jsonSchemaSet,
                $valueObjectDirectory
            );
        }

        $this->addClassConstantsForProperties($fileCollection, ClassConstGenerator::FLAG_PUBLIC);
        $this->addGetterMethodsForProperties($fileCollection);

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
