<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\DisallowedRawHtml;

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\HtmlBlock;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Renderer\HtmlBlockRenderer;
use OCA\LoginNotes\Vendor\League\CommonMark\ConfigurableEnvironmentInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\ExtensionInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\HtmlInline;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Renderer\HtmlInlineRenderer;

final class DisallowedRawHtmlExtension implements ExtensionInterface
{
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $environment->addBlockRenderer(HtmlBlock::class, new DisallowedRawHtmlBlockRenderer(new HtmlBlockRenderer()), 50);
        $environment->addInlineRenderer(HtmlInline::class, new DisallowedRawHtmlInlineRenderer(new HtmlInlineRenderer()), 50);
    }
}
