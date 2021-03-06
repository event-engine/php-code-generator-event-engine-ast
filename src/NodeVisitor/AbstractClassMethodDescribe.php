<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

abstract class AbstractClassMethodDescribe extends NodeVisitorAbstract
{
    public function afterTraverse(array $nodes)
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        $this->addDefinitions($stmt);
                    }
                }
            } elseif ($node instanceof Class_) {
                $this->addDefinitions($node);
            }
        }

        return $newNodes;
    }

    private function addDefinitions(Node\Stmt\Class_ $node): void
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod
                && $stmt->name instanceof Node\Identifier
                && $stmt->name->name === 'describe'
            ) {
                if ($definitions = $this->definitions($stmt)) {
                    $stmt->stmts = \array_merge(
                        $definitions,
                        $stmt->stmts ?? []
                    );
                }
            }
        }
    }

    abstract protected function definitions(Node\Stmt\ClassMethod $node): ?array;

    protected function isAlreadyDefinedForConstant(
        string $methodName,
        string $identifier,
        Node\Stmt\ClassMethod $node
    ): bool {
        if ($node->stmts === null) {
            return false;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Expression
                && $stmt->expr instanceof Node\Expr\MethodCall
            ) {
                if ($stmt->expr->var instanceof Node\Expr\MethodCall) {
                    $alreadyDefined = $this->isAlreadyDefined($methodName, $identifier, $stmt->expr->var);
                } else {
                    $alreadyDefined = $this->isAlreadyDefined($methodName, $identifier, $stmt->expr);
                }

                if ($alreadyDefined === true) {
                    return $alreadyDefined;
                }
            }
        }

        return false;
    }

    private function isAlreadyDefined(
        string $methodName,
        string $identifier,
        Node\Expr\MethodCall $node
    ): bool {
        if ($node->name instanceof Node\Identifier
            && $node->name->name === $methodName
        ) {
            /** @var Node\Arg $arg  */
            $arg = $node->args[0];

            if ($arg->value instanceof Node\Expr\ClassConstFetch) {
                return ((string) $arg->value->name) === $identifier;
            }
        }
        if ($node->var instanceof Node\Expr\MethodCall) {
            return $this->isAlreadyDefined($methodName, $identifier, $node->var);
        }

        return false;
    }
}
