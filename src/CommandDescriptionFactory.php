<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-event-engine-ast for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-event-engine-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\EventEngineAst;

final class CommandDescriptionFactory
{
    /**
     * @var Config\CommandDescription
     **/
    private $config;

    public function __construct(Config\CommandDescription $config)
    {
        $this->config = $config;
    }

    public function config(): Config\CommandDescription
    {
        return $this->config;
    }

    public static function withDefaultConfig(): self
    {
        return new self(Config\CommandDescription::withDefaultConfig());
    }

    public function codeCommandDescription(): Code\CommandDescription
    {
        return new Code\CommandDescription(
            $this->config->getParser(),
            $this->config->getFilterConstName()
        );
    }

    public function codeClassConstant(): Code\ClassConstant
    {
        return new Code\ClassConstant(
            $this->config->getFilterConstName(),
            $this->config->getFilterConstValue()
        );
    }

    public function component(): CommandDescription
    {
        return new CommandDescription(
            $this->config->getParser(),
            $this->config->getPrinter(),
            $this->codeCommandDescription(),
            $this->codeClassConstant(),
        );
    }

    public function componentMetadataSchema(): CommandDescriptionMetadataSchema
    {
        return new CommandDescriptionMetadataSchema(
            $this->config->getFilterConstName()
        );
    }
}
