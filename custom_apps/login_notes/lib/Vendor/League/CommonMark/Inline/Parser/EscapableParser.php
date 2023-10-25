<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Inline\Parser;

use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\Newline;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\Text;
use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\RegexHelper;

final class EscapableParser implements InlineParserInterface
{
    public function getCharacters(): array
    {
        return ['\\'];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $nextChar = $cursor->peek();

        if ($nextChar === "\n") {
            $cursor->advanceBy(2);
            $inlineContext->getContainer()->appendChild(new Newline(Newline::HARDBREAK));

            return true;
        } elseif ($nextChar !== null && RegexHelper::isEscapable($nextChar)) {
            $cursor->advanceBy(2);
            $inlineContext->getContainer()->appendChild(new Text($nextChar));

            return true;
        }

        $cursor->advanceBy(1);
        $inlineContext->getContainer()->appendChild(new Text('\\'));

        return true;
    }
}
