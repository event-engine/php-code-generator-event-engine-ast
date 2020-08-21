<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Node;

final class ClassMethodDescribeAggregate extends AbstractClassMethodDescribe
{
    /**
     * @var IdentifierGenerator
     */
    private $lineGenerator;

    public function __construct(IdentifierGenerator $lineGenerator)
    {
        $this->lineGenerator = $lineGenerator;
    }

    protected function definitions(Node\Stmt\ClassMethod $node): ?array
    {
        $isAlreadyDefined = $this->isAlreadyDefinedForConstant(
            'process',
            $this->lineGenerator->getIdentifier(),
            $node
        );

        return $isAlreadyDefined === false ? $this->lineGenerator->generate() : null;
    }
}
