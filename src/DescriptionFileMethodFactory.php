<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class DescriptionFileMethodFactory
{
    /**
     * @var Config\DescriptionFileMethod
     **/
    private $config;

    public function __construct(Config\DescriptionFileMethod $config)
    {
        $this->config = $config;
    }

    public function config(): Config\DescriptionFileMethod
    {
        return $this->config;
    }

    public static function withDefaultConfig(): self
    {
        return new self(new Config\DescriptionFileMethod());
    }

    public function component(): DescriptionFileMethod
    {
        return new DescriptionFileMethod(
            $this->config->getParser(),
            $this->config->getPrinter()
        );
    }
}
