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

namespace OCA\LoginNotes\Vendor\League\CommonMark\Block\Parser;

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\FencedCode;
use OCA\LoginNotes\Vendor\League\CommonMark\ContextInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Cursor;

final class FencedCodeParser implements BlockParserInterface
{
    public function parse(ContextInterface $context, Cursor $cursor): bool
    {
        if ($cursor->isIndented()) {
            return false;
        }

        $c = $cursor->getCharacter();
        if ($c !== ' ' && $c !== "\t" && $c !== '`' && $c !== '~') {
            return false;
        }

        $indent = $cursor->getIndent();
        $fence = $cursor->match('/^[ \t]*(?:`{3,}(?!.*`)|~{3,})/');
        if ($fence === null) {
            return false;
        }

        // fenced code block
        $fence = \ltrim($fence, " \t");
        $fenceLength = \strlen($fence);
        $context->addBlock(new FencedCode($fenceLength, $fence[0], $indent));

        return true;
    }
}
