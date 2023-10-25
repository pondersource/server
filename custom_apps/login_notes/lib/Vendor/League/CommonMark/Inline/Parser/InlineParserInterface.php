<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Inline\Parser;

use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;

interface InlineParserInterface
{
    /**
     * @return string[]
     */
    public function getCharacters(): array;

    /**
     * @param InlineParserContext $inlineContext
     *
     * @return bool
     */
    public function parse(InlineParserContext $inlineContext): bool;
}
