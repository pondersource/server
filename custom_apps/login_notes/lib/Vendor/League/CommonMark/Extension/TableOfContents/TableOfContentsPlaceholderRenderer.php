<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\TableOfContents;

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\AbstractBlock;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Renderer\BlockRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\ElementRendererInterface;

final class TableOfContentsPlaceholderRenderer implements BlockRendererInterface
{
    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
    {
        return '<!-- table of contents -->';
    }
}
