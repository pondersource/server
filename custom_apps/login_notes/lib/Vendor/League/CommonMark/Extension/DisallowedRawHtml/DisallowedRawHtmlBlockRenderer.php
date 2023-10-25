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

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\AbstractBlock;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Renderer\BlockRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\ElementRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationAwareInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationInterface;

final class DisallowedRawHtmlBlockRenderer implements BlockRendererInterface, ConfigurationAwareInterface
{
    /** @var BlockRendererInterface */
    private $htmlBlockRenderer;

    public function __construct(BlockRendererInterface $htmlBlockRenderer)
    {
        $this->htmlBlockRenderer = $htmlBlockRenderer;
    }

    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
    {
        $rendered = $this->htmlBlockRenderer->render($block, $htmlRenderer, $inTightList);

        if ($rendered === '') {
            return '';
        }

        // Match these types of tags: <title> </title> <title x="sdf"> <title/> <title />
        return preg_replace('/<(\/?(?:title|textarea|style|xmp|iframe|noembed|noframes|script|plaintext)[ \/>])/i', '&lt;$1', $rendered);
    }

    public function setConfiguration(ConfigurationInterface $configuration)
    {
        if ($this->htmlBlockRenderer instanceof ConfigurationAwareInterface) {
            $this->htmlBlockRenderer->setConfiguration($configuration);
        }
    }
}
