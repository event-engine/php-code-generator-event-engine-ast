<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\CodeGenerator\EventEngineAst\Metadata\InspectioJson\MetadataFactory;
use EventEngine\InspectioGraphCody\Node;
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
    /**
     * @var callable
     */
    protected $metadataFactory;

    protected ClassInfoList $classInfoList;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileSystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->metadataFactory = static fn (Node $node) => (new MetadataFactory())($node->metadata() ?? '', $node->type());

        $this->initComposerFile();

        $this->apiAggregateFilename = $this->srcFolder . '/Domain/Api/Aggregate.php';
        $this->apiEventFilename = $this->srcFolder . '/Domain/Api/Event.php';
        $this->apiCommandFilename = $this->srcFolder . '/Domain/Api/Command.php';
        $this->modelPath = $this->srcFolder . '/Domain/Model';

        $this->classInfoList = new ClassInfoList();

        $this->classInfoList->addClassInfo(
            ...Psr4Info::fromComposer(
                '/service',
                $this->fileSystem->read('service/composer.json'),
                FilterFactory::directoryToNamespaceFilter(),
                FilterFactory::namespaceToDirectoryFilter(),
            )
        );
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
