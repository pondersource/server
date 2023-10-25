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

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Footnote\Parser;

use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Parser\InlineParserInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\InlineParserContext;
use OCA\LoginNotes\Vendor\League\CommonMark\Reference\Reference;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationAwareInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationInterface;

final class FootnoteRefParser implements InlineParserInterface, ConfigurationAwareInterface
{
    /** @var ConfigurationInterface */
    private $config;

    public function getCharacters(): array
    {
        return ['['];
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $container = $inlineContext->getContainer();
        $cursor = $inlineContext->getCursor();
        $nextChar = $cursor->peek();
        if ($nextChar !== '^') {
            return false;
        }

        $state = $cursor->saveState();

        $m = $cursor->match('#\[\^([^\]]+)\]#');
        if ($m !== null) {
            if (\preg_match('#\[\^([^\]]+)\]#', $m, $matches) > 0) {
                $container->appendChild(new FootnoteRef($this->createReference($matches[1])));

                return true;
            }
        }

        $cursor->restoreState($state);

        return false;
    }

    private function createReference(string $label): Reference
    {
        return new Reference(
            $label,
            '#' . $this->config->get('footnote/footnote_id_prefix', 'fn:') . $label,
            $label
        );
    }

    public function setConfiguration(ConfigurationInterface $config): void
    {
        $this->config = $config;
    }
}
