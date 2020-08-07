<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cartridge-event-engine for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cartridge-event-engine/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngine\CodeGenerator\Cartridge\EventEngine\Config;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

trait PhpPrinterTrait
{
    /**
     * @var PrettyPrinterAbstract
     **/
    private $printer;

    /**
     * @return PrettyPrinterAbstract
     */
    public function getPrinter(): PrettyPrinterAbstract
    {
        if (null === $this->printer) {
            $this->printer = new Standard(['shortArraySyntax' => true]);
        }

        return $this->printer;
    }

    /**
     * @param PrettyPrinterAbstract $printer
     */
    public function setPrinter(PrettyPrinterAbstract $printer): void
    {
        $this->printer = $printer;
    }
}
