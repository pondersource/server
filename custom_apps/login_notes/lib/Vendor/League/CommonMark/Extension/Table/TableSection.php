<?php

declare(strict_types=1);

/*
 * This is part of the league/commonmark package.
 *
 * (c) Martin Hasoň <martin.hason@gmail.com>
 * (c) Webuni s.r.o. <info@webuni.cz>
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Table;

use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\AbstractBlock;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\AbstractStringContainerBlock;
use OCA\LoginNotes\Vendor\League\CommonMark\Block\Element\InlineContainerInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\ContextInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Cursor;

final class TableSection extends AbstractStringContainerBlock implements InlineContainerInterface
{
    const TYPE_HEAD = 'thead';
    const TYPE_BODY = 'tbody';

    /** @var string */
    public $type = self::TYPE_BODY;

    public function __construct(string $type = self::TYPE_BODY)
    {
        parent::__construct();
        $this->type = $type;
    }

    public function isHead(): bool
    {
        return self::TYPE_HEAD === $this->type;
    }

    public function isBody(): bool
    {
        return self::TYPE_BODY === $this->type;
    }

    public function canContain(AbstractBlock $block): bool
    {
        return $block instanceof TableRow;
    }

    public function isCode(): bool
    {
        return false;
    }

    public function matchesNextLine(Cursor $cursor): bool
    {
        return false;
    }

    public function handleRemainingContents(ContextInterface $context, Cursor $cursor): void
    {
    }
}
