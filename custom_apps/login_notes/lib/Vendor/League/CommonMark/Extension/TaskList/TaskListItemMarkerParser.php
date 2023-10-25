<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\TaskList;

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\ListItem;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\Paragraph;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Parser\InlineParserInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;

final class TaskListItemMarkerParser implements InlineParserInterface
{
    public function getCharacters(): array
    {
        return ['['];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $container = $inlineContext->getContainer();

        // Checkbox must come at the beginning of the first paragraph of the list item
        if ($container->hasChildren() || !($container instanceof Paragraph && $container->parent() && $container->parent() instanceof ListItem)) {
            return false;
        }

        $cursor = $inlineContext->getCursor();
        $oldState = $cursor->saveState();

        $m = $cursor->match('/\[[ xX]\]/');
        if ($m === null) {
            return false;
        }

        if ($cursor->getNextNonSpaceCharacter() === null) {
            $cursor->restoreState($oldState);

            return false;
        }

        $isChecked = $m !== '[ ]';

        $container->appendChild(new TaskListItemMarker($isChecked));

        return true;
    }
}
