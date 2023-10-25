<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 * (c) Rezo Zero / Ambroise Maupate
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Footnote\Renderer;

use OCA\LoginNotes\Vendor\League\CommonMark\ElementRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use OCA\LoginNotes\Vendor\League\CommonMark\HtmlElement;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\AbstractInline;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Renderer\InlineRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationAwareInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationInterface;

final class FootnoteRefRenderer implements InlineRendererInterface, ConfigurationAwareInterface
{
    /** @var ConfigurationInterface */
    private $config;

    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        if (!($inline instanceof FootnoteRef)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . \get_class($inline));
        }

        $attrs = $inline->getData('attributes', []);
        $class = $attrs['class'] ?? $this->config->get('footnote/ref_class', 'footnote-ref');
        $idPrefix = $this->config->get('footnote/ref_id_prefix', 'fnref:');

        return new HtmlElement(
            'sup',
            [
                'id' => $idPrefix . \mb_strtolower($inline->getReference()->getLabel()),
            ],
            new HTMLElement(
                'a',
                [
                    'class' => $class,
                    'href'  => \mb_strtolower($inline->getReference()->getDestination()),
                    'role'  => 'doc-noteref',
                ],
                $inline->getReference()->getTitle()
            ),
            true
        );
    }

    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->config = $configuration;
    }
}
