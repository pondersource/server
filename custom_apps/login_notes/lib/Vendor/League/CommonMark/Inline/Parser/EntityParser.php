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

use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\Text;
use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\Html5EntityDecoder;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\RegexHelper;

final class EntityParser implements InlineParserInterface
{
    public function getCharacters(): array
    {
        return ['&'];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        if ($m = $inlineContext->getCursor()->match('/^' . RegexHelper::PARTIAL_ENTITY . '/i')) {
            $inlineContext->getContainer()->appendChild(new Text(Html5EntityDecoder::decode($m)));

            return true;
        }

        return false;
    }
}
