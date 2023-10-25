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

namespace OCA\LoginNotes\Vendor\League\CommonMark\Inline\Renderer;

use OCA\LoginNotes\Vendor\League\CommonMark\ElementRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\HtmlElement;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\AbstractInline;

interface InlineRendererInterface
{
    /**
     * @param AbstractInline           $inline
     * @param ElementRendererInterface $htmlRenderer
     *
     * @return HtmlElement|string|null
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer);
}
