<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LoginNotes\Model;

use OCP\AppFramework\Db\Entity;

/**
 * Class Note
 *
 * @method void setText(string $text)
 * @method string getText()
 * @method void setRawText(string $rawText)
 * @method string getRawText()
 * @method void setCreatedAt(int $time)
 * @method int getCreatedAt()
 * @method void setPagesEnabled(string|null $pages)
 * @method string|null getPagesEnabled()
 *
 * @package OCA\LoginNotes\Model
 */
class Note extends Entity implements \JsonSerializable
{
	public ?string $text = null;
	public ?string $rawText = null;
	public ?int $createdAt = null;
	public ?string $pagesEnabled = null;

	public function __construct()
	{
		$this->addType('rawText', 'string');
		$this->addType('text', 'string');
		$this->addType('createdAt', 'int');
		$this->addType('pagesEnabled', 'string');
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'text' => $this->text,
			'rawText' => $this->rawText,
			'createdAt' => $this->createdAt,
			'pagesEnabled' => json_decode(($this->pagesEnabled ?? '{"login":true}'), true, 512, JSON_THROW_ON_ERROR),
		];
	}
}
