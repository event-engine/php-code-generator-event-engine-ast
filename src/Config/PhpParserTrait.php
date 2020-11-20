<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst\Config;

use PhpParser\Parser;
use PhpParser\ParserFactory;

trait PhpParserTrait
{
    /**
     * @var Parser
     **/
    private $parser;

    public function getParser(): Parser
    {
        if (null === $this->parser) {
            $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        }

        return $this->parser;
    }

    public function setParser(Parser $parser): void
    {
        $this->parser = $parser;
    }
}
