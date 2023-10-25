<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 * (c) 2015 Martin Hasoň <martin.hason@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Parser;

use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Node\AttributesInline;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Util\AttributesHelper;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\Text;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Parser\InlineParserInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;

final class AttributesInlineParser implements InlineParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCharacters(): array
    {
        return ['{'];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();

        $char = (string) $cursor->peek(-1);

        $attributes = AttributesHelper::parseAttributes($cursor);
        if ($attributes === []) {
            return false;
        }

        if ($char === ' ' && ($previousInline = $inlineContext->getContainer()->lastChild()) instanceof Text) {
            $previousInline->setContent(\rtrim($previousInline->getContent(), ' '));
        }

        if ($char === '') {
            $cursor->advanceToNextNonSpaceOrNewline();
        }

        $node = new AttributesInline($attributes, $char === ' ' || $char === '');
        $inlineContext->getContainer()->appendChild($node);

        return true;
    }
}
