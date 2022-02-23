<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Node;

final class ClassMethodDescribeQuery extends AbstractClassMethodDescribe
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
            'registerQuery',
            $this->lineGenerator->getIdentifier(),
            $node
        );

        return $isAlreadyDefined === false ? $this->lineGenerator->generate() : null;
    }
}
