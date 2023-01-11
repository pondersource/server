<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\WorkflowEngine\Check;

use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Cache\ICache;
use OCP\IL10N;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IFileCheck;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\ISession;

/** @psalm-suppress PropertyNotSetInConstructor */
class MfaVerified implements ICheck, IFileCheck {
	use TFileCheck;

	/** @var array */
	/** @psalm-suppress PropertyNotSetInConstructor */
	/** @psalm-suppress MissingPropertyType */
	protected $fileIds;

	/** @var IL10N */
	protected $l;

	/** @var ISession */
	protected $session;

	/**
	 * @param IL10N $l
	 * @param ISession $session
	 */
	public function __construct(IL10N $l, ISession $session) {
		$this->l = $l;
		$this->session = $session;
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value): bool {
		$mfaVerified = $this->session->get('user_saml.samlUserData')["mfa_verified"][0];
		if ($operator === 'is') {
			return $mfaVerified == '1'; //Mfa verified must not have access
		} else {
			return $mfaVerified != '1';
		}
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value): void {
		if (!in_array($operator, ['is', '!is'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		if (!in_array($value, ['Verified'])) {
			throw new \UnexpectedValueException($this->l->t('The given value is invalid, must be "Verified"'), 1);
		}
	}

	/**
	 * Get the file ids of the given path and its parents
	 * @param ICache $cache
	 * @param string $path
	 * @param bool $isExternalStorage
	 * @return int[]
	 */
	protected function getFileIds(ICache $cache, $path, $isExternalStorage) {
		/** @psalm-suppress InvalidArgument */
		if ($this->storage->instanceOfStorage(\OCA\GroupFolders\Mount\GroupFolderStorage::class)) {
			// Special implementation for groupfolder since all groupfolders share the same storage
			// id so add the group folder id in the cache key too.
			$groupFolderStorage = $this->storage;
			if ($this->storage instanceof Wrapper) {
				$groupFolderStorage = $this->storage->getInstanceOfStorage(\OCA\GroupFolders\Mount\GroupFolderStorage::class);
			}
			if ($groupFolderStorage === null) {
				throw new \LogicException('Should not happen: Storage is instance of GroupFolderStorage but no group folder storage found while unwrapping.');
			}
			/**
			 * @psalm-suppress UndefinedDocblockClass
			 * @psalm-suppress UndefinedInterfaceMethod
			 */
			$cacheId = $cache->getNumericStorageId() . '/' . $groupFolderStorage->getFolderId();
		} else {
			$cacheId = $cache->getNumericStorageId();
		}
		if (isset($this->fileIds[$cacheId][$path])) {
			return $this->fileIds[$cacheId][$path];
		}

		$parentIds = [];
		if ($path !== $this->dirname($path)) {
			$parentIds = $this->getFileIds($cache, $this->dirname($path), $isExternalStorage);
		} elseif (!$isExternalStorage) {
			return [];
		}

		$fileId = $cache->getId($path);
		if ($fileId !== -1) {
			$parentIds[] = $fileId;
		}

		$this->fileIds[$cacheId][$path] = $parentIds;

		return $parentIds;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function dirname(string $path): string {
		$dir = dirname($path);
		return $dir === '.' ? '' : $dir;
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
