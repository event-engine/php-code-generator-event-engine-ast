<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Config\EventEngineConfig;
use EventEngine\CodeGenerator\EventEngineAst\Config\Naming;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredNaming;
use EventEngine\CodeGenerator\EventEngineAst\Metadata;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\EventSourcingGraph;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use OpenCodeModeling\Filter\FilterFactory;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected const FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;

    protected string $basePath = '/service';

    protected string $srcFolder = '/service/src';

    protected string $appNamespace = 'MyService';

    protected string $apiAggregateFilename;

    protected string $apiEventFilename;

    protected string $apiCommandFilename;

    protected string $modelPath;

    protected Filesystem $fileSystem;

    protected EventSourcingAnalyzer $analyzer;

    protected Naming $config;

    /**
     * @var callable
     */
    protected $metadataFactory;

    protected ClassInfoList $classInfoList;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileSystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->metadataFactory = new Metadata\MetadataFactory(new Metadata\InspectioJson\MetadataFactory());
        $this->analyzer = new EventSourcingAnalyzer(
            new EventSourcingGraph(FilterFactory::constantNameFilter(), $this->metadataFactory)
        );

        $this->initComposerFile();

        $this->classInfoList = new ClassInfoList();

        $this->classInfoList->addClassInfo(
            ...Psr4Info::fromComposer(
                '/service',
                $this->fileSystem->read('service/composer.json'),
                FilterFactory::directoryToNamespaceFilter(),
                FilterFactory::namespaceToDirectoryFilter(),
            )
        );

        $config = new EventEngineConfig();
        $config->setBasePath($this->basePath);
        $config->setClassInfoList($this->classInfoList);

        $this->config = new PreConfiguredNaming($config);
    }

    private function initComposerFile(): void
    {
        $composerFile = <<<'JSON'
{
    "autoload": {
        "psr-4": {
            "MyService\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MyServiceTest\\": "tests/"
        }
    }
}

JSON;

        $this->fileSystem->write($this->basePath . DIRECTORY_SEPARATOR . 'composer.json', $composerFile);
    }
}
