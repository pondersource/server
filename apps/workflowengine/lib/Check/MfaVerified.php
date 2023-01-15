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
		$mfaVerified = $this->session->get('user_saml.samlUserData')["mfa_verified"][0] ?? false;
		if ($operator === 'is') {
			return $mfaVerified === '1'; // checking whether the current user is MFA-verified
		} else {
			return $mfaVerified !== '1'; // checking whether the current user is not MFA-verified
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
