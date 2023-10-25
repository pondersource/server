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

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Parser\BlockParserInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\ContextInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Cursor;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\TableOfContents\Node\TableOfContentsPlaceholder;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationAwareInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Util\ConfigurationInterface;

final class TableOfContentsPlaceholderParser implements BlockParserInterface, ConfigurationAwareInterface
{
    /** @var ConfigurationInterface */
    private $config;

    public function parse(ContextInterface $context, Cursor $cursor): bool
    {
        $placeholder = $this->config->get('table_of_contents/placeholder');
        if ($placeholder === null) {
            return false;
        }

        // The placeholder must be the only thing on the line
        if ($cursor->match('/^' . \preg_quote($placeholder, '/') . '$/') === null) {
            return false;
        }

        $context->addBlock(new TableOfContentsPlaceholder());

        return true;
    }

    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->config = $configuration;
    }
}
