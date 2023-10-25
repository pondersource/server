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

namespace OCA\LoginNotes;

use OCA\LoginNotes\Vendor\League\CommonMark\Environment;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\CommonMarkCoreExtension;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use OCA\LoginNotes\Vendor\League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use OCA\LoginNotes\Vendor\League\CommonMark\MarkdownConverter;
use OCA\LoginNotes\Model\Note;
use OCA\LoginNotes\Model\NoteMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;

class Manager
{
	private NoteMapper $noteMapper;
	private MarkdownConverter $converter;
	private ITimeFactory $timeFactory;

	public function __construct(NoteMapper $noteMapper, ITimeFactory $timeFactory)
	{
		$this->noteMapper = $noteMapper;
		$this->timeFactory = $timeFactory;

		$config = [
			'external_link' => [
				'open_in_new_window' => true,
				'nofollow' => '',
				'noopener' => 'external',
				'noreferrer' => 'external',
			],
		];

		$environment = new Environment($config);
		$environment->addExtension(new CommonMarkCoreExtension());
		$environment->addExtension(new GithubFlavoredMarkdownExtension());
		$environment->addExtension(new ExternalLinkExtension());
		$this->converter = new MarkdownConverter($environment);
	}

	/**
	 * @param int $id
	 * @return Note
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException|Exception
	 */
	public function getById(int $id): Note
	{
		return $this->noteMapper->getById($id);
	}

	/**
	 * @param string $text
	 * @param array $pages
	 * @return Note
	 * @throws Exception
	 * @throws \JsonException
	 * @psalm-return Note
	 */
	public function create(string $text, array $pages): Note
	{
		$text = trim($text);
		$note = new Note();
		$note->setRawText($text);
		$note->setText($this->converter->convertToHtml($text));
		$note->setCreatedAt($this->timeFactory->getTime());
		$note->setPagesEnabled(json_encode($pages, JSON_THROW_ON_ERROR));
		return $this->noteMapper->insert($note);
	}

	/**
	 * @param int $noteId
	 * @param string $text
	 * @param array $pages
	 * @return Note
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 * @throws \JsonException
	 */
	public function update(int $noteId, string $text, array $pages): Note
	{
		$text = trim($text);
		$note = $this->noteMapper->getById($noteId);
		$note->setRawText($text);
		$note->setText($this->converter->convertToHtml($text));
		$note->setPagesEnabled(json_encode($pages, JSON_THROW_ON_ERROR));
		$this->noteMapper->update($note);
		return $note;
	}

	/**
	 * @param Note $note
	 * @throws Exception
	 */
	public function delete(Note $note): void
	{
		$this->noteMapper->delete($note);
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 * @throws Exception
	 */
	public function getNotes(?int $limit = null, ?int $offset = null): array
	{
		return $this->noteMapper->getNotes($limit, $offset);
	}
}
