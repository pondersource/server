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

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Footnote\Node;

use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\AbstractInline;
use OCA\LoginNotes\Vendor\League\CommonMark\Reference\ReferenceInterface;

/**
 * Link from the footnote on the bottom of the document back to the reference
 */
final class FootnoteBackref extends AbstractInline
{
    /** @var ReferenceInterface */
    private $reference;

    public function __construct(ReferenceInterface $reference)
    {
        $this->reference = $reference;
    }

    public function getReference(): ReferenceInterface
    {
        return $this->reference;
    }
}
