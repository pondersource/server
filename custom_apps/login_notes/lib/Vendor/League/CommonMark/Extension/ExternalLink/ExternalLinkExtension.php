<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OCA\LoginNotes\Vendor\League\CommonMark\Extension\ExternalLink;

use OCA\LoginNotes\Vendor\League\CommonMark\ConfigurableEnvironmentInterface;
use OCA\LoginNotes\Vendor\League\CommonMark\Event\DocumentParsedEvent;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\ExtensionInterface;

final class ExternalLinkExtension implements ExtensionInterface
{
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $environment->addEventListener(DocumentParsedEvent::class, new ExternalLinkProcessor($environment), -50);
    }
}
