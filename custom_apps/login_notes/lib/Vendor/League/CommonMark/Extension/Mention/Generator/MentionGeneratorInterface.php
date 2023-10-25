<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Mention\Generator;

use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Mention\Mention;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\AbstractInline;

interface MentionGeneratorInterface
{
    /**
     * @param Mention $mention
     *
     * @return AbstractInline|null
     */
    public function generateMention(Mention $mention): ?AbstractInline;
}
