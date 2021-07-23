<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\EventEngineAst\NodeVisitor;

use EventEngine\CodeGenerator\EventEngineAst\NodeVisitor\ClassMap;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Standard
     */
    private $printer;

    public function setUp(): void
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard(['shortArraySyntax' => true]);
    }

    /**
     * @test
     */
    public function it_adds_entry_to_class_map(): void
    {
        $ast = $this->parser->parse($this->apiDescriptionClassCode());

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NamespaceUse('MyService\Domain\Model\Building\Command\AddBuilding'));
        $nodeTraverser->addVisitor(new ClassMap('ADD_BUILDING', 'AddBuilding'));

        $this->assertSame($this->expectedApiDescriptionClassCode(), $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_does_not_add_entry_to_class_map_twice(): void
    {
        $ast = $this->parser->parse($this->expectedApiDescriptionClassCode());

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NamespaceUse('MyService\Domain\Model\Building\Command\AddBuilding'));
        $nodeTraverser->addVisitor(new ClassMap('ADD_BUILDING', 'AddBuilding'));

        $this->assertSame($this->expectedApiDescriptionClassCode(), $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function apiDescriptionClassCode(): string
    {
        return <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Api;

use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\JsonSchemaArray;
final class Command implements EventEngineDescription
{
    public const ADD_BUILDING = 'add_building';
    public const CLASS_MAP = [];
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->registerCommand(self::ADD_BUILDING, new JsonSchemaArray(\json_decode(file_get_contents('/service/src/Domain/Api/_schema/ADD_BUILDING.json'), true, 512, \JSON_THROW_ON_ERROR)));
    }
}
PHP;
    }

    private function expectedApiDescriptionClassCode(): string
    {
        return <<<'PHP'
<?php

declare (strict_types=1);
namespace MyService\Domain\Api;

use MyService\Domain\Model\Building\Command\AddBuilding;
use EventEngine\EventEngine;
use EventEngine\EventEngineDescription;
use EventEngine\JsonSchema\JsonSchema;
use EventEngine\JsonSchema\JsonSchemaArray;
final class Command implements EventEngineDescription
{
    public const ADD_BUILDING = 'add_building';
    public const CLASS_MAP = [self::ADD_BUILDING => AddBuilding::class];
    public static function describe(EventEngine $eventEngine) : void
    {
        $eventEngine->registerCommand(self::ADD_BUILDING, new JsonSchemaArray(\json_decode(file_get_contents('/service/src/Domain/Api/_schema/ADD_BUILDING.json'), true, 512, \JSON_THROW_ON_ERROR)));
    }
}
PHP;
    }
}
