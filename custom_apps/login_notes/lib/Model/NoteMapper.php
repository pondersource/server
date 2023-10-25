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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;
use function is_int;

/**
 * @template-extends QBMapper<Note>
 */
class NoteMapper extends QBMapper
{
	public const DB_NAME = 'login_notes';

	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, self::DB_NAME, Note::class);
	}

	/**
	 * @param int $id
	 * @return Note
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException|Exception
	 */
	public function getById(int $id): Note
	{
		$query = $this->db->getQueryBuilder();

		$query->select('*')
			->from($this->getTableName())
			->where(
				$query->expr()->eq('id', $query->createNamedParameter($id))
			);

		return $this->findEntity($query);
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Note[]
	 * @psalm-return Note[]
	 * @throws Exception
	 */
	public function getNotes(int $limit = null, int $offset = null): array
	{
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from(self::DB_NAME)
			->orderBy('created_at', 'DESC')
		;
		if (is_int($limit)) {
			$query->setMaxResults($limit);
		}
		if (is_int($offset)) {
			$query->setFirstResult($offset);
		}

		return $this->findEntities($query);
	}
}
