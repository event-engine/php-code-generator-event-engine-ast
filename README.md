# Event Engine - PHP Code Generator via PHP AST

PHP Code Generator based on PHP Abstract Syntax Tree. It provides a comprehensive high level API to
generate PHP code from [prooph board](https://prooph-board.com/ "prooph board") for [Event Engine](https://event-engine.github.io/ "Event Engine").

It supports the following code generation:

- Event Engine API description for commands, aggregates and domain events
- Command, aggregate and domain event classes with corresponding value objects based on metadata (JSON schema)
- Glue code between command, corresponding aggregate and corresponding domain events 

## Installation

Run the following to install this library:

```bash
$ composer require event-engine/php-code-generator-event-engine-ast
```

## Usage

The code generation is based on the [InspectIO Graph](https://github.com/event-engine/php-inspectio-graph "InspectIO Graph").
There are two implementations of *InspectIO Graph*. The first one is based on the [InspectIO GraphML graph format](https://github.com/event-engine/php-inspectio-graph-ml "InspectIO Graph GraphML") 
and the second is based on the [InspectIO Cody graph format](https://github.com/event-engine/php-inspectio-graph-cody "InspectIO Graph Cody").

> It is recommended to use the [InspectIO Cody graph format](https://github.com/event-engine/php-inspectio-graph-cody "InspectIO Graph Cody")
because it's based on a simple JSON structure. 

For out-of-the-box usage you can use the preconfigured configuration file `Config\PreConfiguredNaming`. You are free to 
change the configuration for your needs. The following example uses the preconfigured configurations.

> Feel free to modify the generated PHP code, because your changes will *NOT* be overwritten (can be overwritten if you want)!

### Code Generation

The following quick example shows how to generate PHP code for *Command* classes with the preconfigured configuration.

- Please see command unit tests (`tests/CommantTest.php`) for comprehensive examples which code will be generated.
- Please see event unit tests (`tests/EventTest.php`) for comprehensive examples which code will be generated.
- Please see aggregate unit tests (`tests/AggregateTest.php`) for comprehensive examples which code will be generated.
- Please see query unit tests (`tests/QueryTest.php`) for comprehensive examples which code will be generated.
- Please see value object unit tests (`tests/ValueObjectTest.php`) for comprehensive examples which code will be generated.

```php
<?php

declare(strict_types=1);

use EventEngine\CodeGenerator\EventEngineAst\Command;
use EventEngine\CodeGenerator\EventEngineAst\Config\EventEngineConfig;
use EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredNaming;
use EventEngine\CodeGenerator\EventEngineAst\Metadata;
use EventEngine\InspectioGraphCody\EventSourcingAnalyzer;
use EventEngine\InspectioGraphCody\EventSourcingGraph;

$contextName = 'Acme';
$basePath = '../app';
$composerFile = $basePath . '/composer.json';

$config = new EventEngineConfig();
$config->setBasePath($basePath);
$config->addComposerInfo($composerFile);

$namingConfig = new PreConfiguredNaming($config);
$namingConfig->setDefaultContextName($contextName);

$analyzer = new EventSourcingAnalyzer(
    new EventSourcingGraph(
        $config->getFilterConstName(),
        new Metadata\MetadataFactory(new Metadata\InspectioJson\MetadataFactory())
    )
);
// create class to generate code for commands
// same behaviour for the other classes e.g. EventEngine\CodeGenerator\EventEngineAst\Event
$commandGenerator = new Command($namingConfig);

// contains all generated PHP classes
$fileCollection = \OpenCodeModeling\CodeAst\Builder\FileCollection::emptyList();

// path where the Command API Event Engine description should be generated based on Composer autoloader info
$apiCommandFilename = 'src/Domain/Api/Command.php';

// assume that $codyNode is an instance of \EventEngine\InspectioGraphCody\Node which describes a command
$connection = $analyzer->analyse($codyNode);

// generate JSON schema file of the command
$schemas = $commandGenerator->generateJsonSchemaFile($connection, $analyzer);

foreach ($schemas as $schema) {
    $schema['filename']; // contains path with filename depending on your configuration
    $schema['code']; // contains generated JSON schema
}

// call the different generate methods of the code generator class
$commandGenerator->generateApiDescription($connection, $analyzer, $fileCollection);
$commandGenerator->generateApiDescriptionClassMap($connection, $analyzer, $fileCollection);
$commandGenerator->generateCommandFile($connection, $analyzer, $fileCollection);

$files = $config->getObjectGenerator()->generateFiles($fileCollection);

// loop over files and store them in filesystem
foreach ($files as $file) {
    $file['filename']; // contains path with filename depending on your configuration e.g. src/Domain/Aggregate
    $file['code']; // contains generated PHP code
}

```
