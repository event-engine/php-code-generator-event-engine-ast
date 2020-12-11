<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst;

use EventEngine\InspectioGraphCody\Metadata\NodeJsonMetadataFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected const FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;

    protected string $basePath = '/service';
    protected string $srcFolder = '/service/src';
    protected string $appNamespace = 'MyService';

    protected Filesystem $fileSystem;
    protected NodeJsonMetadataFactory $metadataFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileSystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->metadataFactory = new NodeJsonMetadataFactory();

        $this->initComposerFile();
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
