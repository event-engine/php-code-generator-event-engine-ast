<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassMap extends NodeVisitorAbstract
{
    private string $constName;

    private string $className;

    public function __construct(string $constName, string $className)
    {
        $this->constName = $constName;
        $this->className = $className;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        foreach ($stmt->stmts as $stmtClass) {
                            if (
                                $stmtClass instanceof Stmt\ClassConst
                                && $stmtClass->consts[0]->name->name === 'CLASS_MAP'
                                && $stmtClass->consts[0]->value instanceof Node\Expr\Array_
                            ) {
                                $stmtClass->consts[0]->value = $this->addMapIfNotExists($stmtClass->consts[0]->value);
                            }
                        }
                    }
                }
            } elseif ($node instanceof Stmt\Class_) {
                foreach ($node->stmts as $stmtClass) {
                    if (
                        $stmtClass instanceof Stmt\ClassConst
                        && $stmtClass->consts[0]->name->name === 'CLASS_MAP'
                        && $stmtClass->consts[0]->value instanceof Node\Expr\Array_
                    ) {
                        $stmtClass->consts[0]->value = $this->addMapIfNotExists($stmtClass->consts[0]->value);
                    }
                }
            }
        }

        return $newNodes;
    }

    private function addMapIfNotExists(Node\Expr\Array_ $node): Node\Expr\Array_
    {
        $found = false;

        foreach ($node->items as $item) {
            if (
                $item instanceof Node\Expr\ArrayItem
                && $item->value instanceof Node\Expr\ClassConstFetch
                && $item->value->class->toString() === $this->className
            ) {
                $found = true;
            }
        }

        if ($found === false) {
            $node->items[] = new Node\Expr\ArrayItem(
                new Node\Expr\ClassConstFetch(new Node\Name($this->className), 'class'),
                new Node\Expr\ClassConstFetch(new Node\Name('self'), $this->constName),
            );
        }

        return $node;
    }
}
