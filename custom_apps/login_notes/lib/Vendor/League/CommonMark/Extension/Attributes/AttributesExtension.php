<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 * (c) 2015 Martin Hasoň <martin.hason@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes;

use OCA\LoginNotes\Vendor\League\CommonMark\ConfigurableEnvironmentInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Event\DocumentParsedEvent;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Event\AttributesListener;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Parser\AttributesBlockParser;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\Attributes\Parser\AttributesInlineParser;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\ExtensionInterface;

final class AttributesExtension implements ExtensionInterface
{
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $environment->addBlockParser(new AttributesBlockParser());
        $environment->addInlineParser(new AttributesInlineParser());
        $environment->addEventListener(DocumentParsedEvent::class, [new AttributesListener(), 'processDocument']);
    }
}
