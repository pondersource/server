<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\TaskList;

use OCA\LoginNotes\Vendor\League\CommonMark\ElementRendererInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\HtmlElement;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Element\AbstractInline;
use OCA\LoginNotes\Vendor\League\CommonMark\Inline\Renderer\InlineRendererInterface;

final class TaskListItemMarkerRenderer implements InlineRendererInterface
{
    /**
     * @param TaskListItemMarker       $inline
     * @param ElementRendererInterface $htmlRenderer
     *
     * @return HtmlElement|string|null
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        if (!($inline instanceof TaskListItemMarker)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . \get_class($inline));
        }

        $checkbox = new HtmlElement('input', [], '', true);

        if ($inline->isChecked()) {
            $checkbox->setAttribute('checked', '');
        }

        $checkbox->setAttribute('disabled', '');
        $checkbox->setAttribute('type', 'checkbox');

        return $checkbox;
    }
}
