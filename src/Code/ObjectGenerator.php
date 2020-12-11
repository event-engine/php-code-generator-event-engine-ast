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
use OpenCodeModeling\CodeAst\Package\ClassInfo;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\JsonSchemaToPhp\Type\ObjectType;
use OpenCodeModeling\JsonSchemaToPhp\Type\TypeSet;
use OpenCodeModeling\JsonSchemaToPhpAst\ClassGenerator;
use OpenCodeModeling\JsonSchemaToPhpAst\FileGenerator;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class ObjectGenerator
{
    /**
     * @var Parser
     **/
    private $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    /**
     * @var callable
     */
    private $classNameFilter;

    /**
     * @var callable
     */
    private $propertyNameFilter;

    /**
     * @var callable
     */
    private $methodNameFilter;

    /**
     * @var callable
     */
    private $constNameFilter;

    /**
     * @var callable
     */
    private $constValueFilter;

    /**
     * @var ClassInfoList
     **/
    private $classInfoList;

    private ClassGenerator $classGenerator;

    private FileGenerator $fileGenerator;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList,
        ClassGenerator $classGenerator,
        FileGenerator $fileGenerator,
        callable $classNameFilter,
        callable $propertyNameFilter,
        callable $methodNameFilter,
        callable $constNameFilter,
        callable $constValueFilter
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
        $this->classGenerator = $classGenerator;
        $this->fileGenerator = $fileGenerator;
        $this->classNameFilter = $classNameFilter;
        $this->propertyNameFilter = $propertyNameFilter;
        $this->methodNameFilter = $methodNameFilter;
        $this->constNameFilter = $constNameFilter;
        $this->constValueFilter = $constValueFilter;
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

    public function generateFiles(FileCollection $fileCollection): array
    {
        $files = [];

        $this->sortThings($fileCollection);

        $currentFileAst = function (File $classBuilder, ClassInfo $classInfo) {
            $path = $classInfo->getPath($classBuilder->getNamespace() . '\\' . $classBuilder->getName());
            $filename = $classInfo->getFilenameFromPathAndName($path, $classBuilder->getName());

            $code = '';

            if (\file_exists($filename) && \is_readable($filename)) {
                $code = \file_get_contents($filename);
            }

            return $this->parser->parse($code);
        };

        foreach ($this->fileGenerator->generateFiles($fileCollection, $this->parser, $this->printer,
            $currentFileAst) as $filename => $code) {
            $files[$filename] = [
                'filename' => $filename,
                'code' => $code,
            ];
        }

        return $files;
    }

    public function addConstants(FileCollection $fileCollection, int $visibility): void
    {
        $this->classGenerator->addClassConstantsForProperties(
            $fileCollection,
            $this->constNameFilter,
            $this->constValueFilter,
            $visibility
        );
    }

    public function addGetterMethods(FileCollection $fileCollection): void
    {
        $this->classGenerator->addGetterMethods(
            $fileCollection,
            true,
            $this->methodNameFilter,
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
            $file->addNamespaceImport(
                'EventEngine\Data\ImmutableRecord',
                'EventEngine\Data\ImmutableRecordLogic',
            );
            $file->addImplement('ImmutableRecord')
                ->addTrait('ImmutableRecordLogic');
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

        $this->classGenerator->generateClasses(
            $classBuilder,
            $fileCollection,
            $jsonSchemaSet,
            $valueObjectDirectory
        );

        return $fileCollection;
    }

    public function generateObject(
        string $valueObjectDirectory,
        string $className,
        ObjectType $jsonSchema
    ): FileCollection {
        $className = ($this->classNameFilter)($className);
        $classInfo = $this->classInfoList->classInfoForPath($valueObjectDirectory);

        $classBuilder = ClassBuilder::fromScratch(
            $className,
            $classInfo->getClassNamespaceFromPath($valueObjectDirectory)
        )->setFinal(true);

        $fileCollection = FileCollection::emptyList();

        $this->classGenerator->generateClasses(
            $classBuilder,
            $fileCollection,
            new TypeSet($jsonSchema),
            $valueObjectDirectory
        );
        $this->addGetterMethods($fileCollection);

        return $fileCollection;
    }

    public function generateImmutableRecord(
        string $valueObjectDirectory,
        string $valueObjectName,
        ObjectType $jsonSchema
    ): FileCollection {
        $fileCollection = $this->generateObject($valueObjectDirectory, $valueObjectName, $jsonSchema);

        $this->addConstants($fileCollection, ClassConstGenerator::FLAG_PUBLIC);
        $this->addImmutableRecordLogic($fileCollection);

        return $fileCollection;
    }
}
