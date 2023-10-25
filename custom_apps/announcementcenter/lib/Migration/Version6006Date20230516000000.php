<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Remove previous default config and user preference of activity setting
 * They are no long editable since we have direct emails, but they still send
 * otherwise.
 */
class Version6006Date20230516000000 extends SimpleMigrationStep {
	protected IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// Remove former activity default setting
		$query = $this->connection->getQueryBuilder();
		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('activity')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('notify_email_announcementcenter')));
		$query->executeStatement();

		// Remove former activity user preference
		$query = $this->connection->getQueryBuilder();
		$query->delete('preferences')
			->where($query->expr()->eq('appid', $query->createNamedParameter('activity')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('notify_email_announcementcenter')));
		$query->executeStatement();
	}
}
