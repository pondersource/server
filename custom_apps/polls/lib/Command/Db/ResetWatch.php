<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Command\Db;

use OCA\Polls\Command\Command;
use OCA\Polls\Db\IndexManager;
use OCA\Polls\Db\TableManager;
use OCA\Polls\Db\Watch;
use OCA\Polls\Migration\TableSchema;

class ResetWatch extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:reset-watch';
	protected string $description = 'Resets the Watch table';
	protected array $operationHints = [
		'All polls tables will get checked against the current schema.',
		'NO data migration will be executed, so make sure you have a backup of your database.',
	];

	public function __construct(
		private IndexManager $indexManager,
		private TableManager $tableManager
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->resetWatch();
		$this->tableManager->migrate();
		
		$this->indexManager->refreshSchema();
		$this->createIndex();
		$this->indexManager->migrate();

		return 0;
	}

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function resetWatch(): void {
		$messages = [];

		$this->printComment('- Reset Watch table');
		// Remove all indices
		// drop and add watch with current schema
		$messages = array_merge($messages, $this->tableManager->resetWatch());

		// add indices again
		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}
	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function createIndex(): void {
		$tableName = Watch::TABLE;
		$values = TableSchema::UNIQUE_INDICES[$tableName];
		$messages = [];

		$this->printComment('- Create watch index');
		// Remove all indices
		$messages[] = $this->indexManager->createIndex($tableName, $values['name'], $values['columns'], $values['unique']);
		
		// add indices again
		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}
}
