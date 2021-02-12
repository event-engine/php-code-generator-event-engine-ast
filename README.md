# Event Engine - PHP Code Generator via PHP AST

PHP Code Generator based on PHP Abstract Syntax Tree. It provides a comprehensive high level API to
generate PHP code from [InspectIO](https://github.com/event-engine/inspectio "InspectIO") for [Event Engine](https://event-engine.io "Event Engine").

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

For out-of-the-box usage you can use one of the preconfigured *Command* (`Config\PreConfiguredCommand`), 
*Aggregate* (`Config\PreConfiguredAggregate`) and *Event* (`Config\PreConfiguredEvent`) configurations. You are free to 
change the configuration for your needs. The following example uses the preconfigured configurations.

> Feel free to modify the generated PHP code, because your changes will *NOT* be overwritten (can be overwritten if you want)!

### Command Code Generation

The following quick example shows how to generate PHP code for *Command* classes with the preconfigured configuration.

> Please see command unit tests (`tests/CommantTest.php`) for comprehensive examples which code will be generated.

```php
<?php
// Assume $command is an instance of \EventEngine\InspectioGraph\CommandType
// Assume $analyzer is an instance of \EventEngine\InspectioGraph\EventSourcingAnalyzer

$commandConfig = new \EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredCommand();

// configure namespaces for code generation via Composer
$commandConfig->addComposerInfo('[path to your composer.json file]');

// create instance for command code generation
$command = new \EventEngine\CodeGenerator\EventEngineAst\Command($commandConfig);

// contains all generated PHP classes
$fileCollection = \OpenCodeModeling\CodeAst\Builder\FileCollection::emptyList();

// path where the Command API Event Engine description should be generated based on Composer autoloader info
$apiCommandFilename = 'src/Domain/Api/Command.php';

// generate command API description based on the InspectIO Graph info
$command->generateApiDescription($analyzer, $fileCollection, $apiCommandFilename);

// generate Command file with corresponding value objects (if any)
$command->generateCommandFile($analyzer, $fileCollection);

// generate PHP code with filenames
$files = $commandConfig->getObjectGenerator()->generateFiles($fileCollection);

// loop over files and store them in filesystem
foreach ($files as $file) {
    $file['filename']; // contains path with filename depending on your configuration e.g. src/Domain/Command
    $file['code']; // contains generated PHP code
}
```

### Domain Event Code Generation

The following quick example shows how to generate PHP code for *Domain Event* classes with the preconfigured configuration.

> Please see event unit tests (`tests/EventTest.php`) for comprehensive examples which code will be generated.

```php
<?php
// Assume $event is an instance of \EventEngine\InspectioGraph\EventType
// Assume $analyzer is an instance of \EventEngine\InspectioGraph\EventSourcingAnalyzer

$eventConfig = new \EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredEvent();

// configure namespaces for code generation via Composer
$eventConfig->addComposerInfo('[path to your composer.json file]');

// create instance for command code generation
$event = new \EventEngine\CodeGenerator\EventEngineAst\Event($eventConfig);

// contains all generated PHP classes
$fileCollection = \OpenCodeModeling\CodeAst\Builder\FileCollection::emptyList();

// path where the Event API Event Engine description should be generated based on Composer autoloader info
$apiEventFilename = 'src/Domain/Api/Event.php';

// generate command API description based on the InspectIO Graph info
$event->generateApiDescription($analyzer, $fileCollection, $apiEventFilename);

// generate Event file with corresponding value objects (if any)
$event->generateEventFile($analyzer, $fileCollection);

// generate PHP code with filenames
$files = $eventConfig->getObjectGenerator()->generateFiles($fileCollection);

// loop over files and store them in filesystem
foreach ($files as $file) {
    $file['filename']; // contains path with filename depending on your configuration e.g. src/Domain/Event
    $file['code']; // contains generated PHP code
}

```

### Aggregate Code Generation

The following quick example shows how to generate PHP code for *Aggregate* classes with the preconfigured configuration.

> Please see event unit tests (`tests/AggregateTest.php`) for comprehensive examples which code will be generated.

```php
<?php
// Assume $aggregate is an instance of \EventEngine\InspectioGraph\AggregateType
// Assume $analyzer is an instance of \EventEngine\InspectioGraph\EventSourcingAnalyzer

$aggregateConfig = new \EventEngine\CodeGenerator\EventEngineAst\Config\PreConfiguredAggregate();

// configure namespaces for code generation via Composer
$aggregateConfig->addComposerInfo('[path to your composer.json file]');

// create instance for aggregate code generation
$aggregate = new \EventEngine\CodeGenerator\EventEngineAst\Aggregate($aggregateConfig);

// contains all generated PHP classes
$fileCollection = \OpenCodeModeling\CodeAst\Builder\FileCollection::emptyList();

// path where the Aggregate API Event Engine description should be generated based on Composer autoloader info
$apiAggregateFilename = 'src/Domain/Api/Aggregate.php';

// generate aggregate API description based on the InspectIO Graph info
$aggregate->generateApiDescription($analyzer, $fileCollection, $apiAggregateFilename);

// generate Aggregate file with corresponding value objects (if any)
$aggregate->generateAggregateFile($analyzer, $fileCollection, $apiEventFilename);

// generate Aggregate state file to store state changes (ImmutableRecord)
$aggregate->generateAggregateStateFile($analyzer, $fileCollection);

// generate PHP code with filenames
$files = $aggregateConfig->getObjectGenerator()->generateFiles($fileCollection);

// loop over files and store them in filesystem
foreach ($files as $file) {
    $file['filename']; // contains path with filename depending on your configuration e.g. src/Domain/Aggregate
    $file['code']; // contains generated PHP code
}
```
