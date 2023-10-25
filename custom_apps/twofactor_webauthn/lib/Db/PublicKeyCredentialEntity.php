<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\TwoFactorWebauthn\Db;

use OCP\AppFramework\Db\Entity;
use Ramsey\Uuid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPathLoader;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getPublicKeyCredentialId()
 * @method void setPublicKeyCredentialId(string $id)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getTransports()
 * @method void setTransports(string $type)
 * @method string getAttestationType()
 * @method void setAttestationType(string $type)
 * @method string getTrustPath()
 * @method void setTrustPath(string $path)
 * @method string getAaguid()
 * @method void setAaguid(string $aaguid)
 * @method string getCredentialPublicKey()
 * @method void setCredentialPublicKey(string $key)
 * @method string getUserHandle()
 * @method void setUserHandle(string $handle)
 * @method int getCounter()
 * @method void setCounter(int $counter)
 * @method bool|null isActive()
 * @method void setActive(bool $active)
 * @method int|null getCreatedAt()
 * @method void setCreatedAt(?int $counter)
 */
class PublicKeyCredentialEntity extends Entity {
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $publicKeyCredentialId;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $transports;

	/**
	 * @var string
	 */
	protected $attestationType;

	/**
	 * @var string
	 */
	protected $trustPath;

	/**
	 * @var string
	 */
	protected $aaguid;

	/**
	 * @var string
	 */
	protected $credentialPublicKey;

	/**
	 * @var string
	 */
	protected $userHandle;

	/**
	 * @var int
	 */
	protected $counter;

	/**
	 * @var bool|null
	 */
	protected $active;

	/** @var int|null */
	protected $createdAt;

	public function __construct() {
		$this->addType('counter', 'int');
		$this->addType('active', 'boolean');
		$this->addType('createdAt', 'int');
	}

	public static function fromPublicKeyCrendentialSource(string $name,
														  PublicKeyCredentialSource $publicKeyCredentialSource,
														  ?int $ctime): PublicKeyCredentialEntity {
		$publicKeyCredentialEntity = new self();

		$publicKeyCredentialEntity->setName($name);
		$publicKeyCredentialEntity->setPublicKeyCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
		$publicKeyCredentialEntity->setType($publicKeyCredentialSource->getType());
		$publicKeyCredentialEntity->setTransports(json_encode($publicKeyCredentialSource->getTransports()));
		$publicKeyCredentialEntity->setAttestationType($publicKeyCredentialSource->getAttestationType());
		$publicKeyCredentialEntity->setTrustPath(json_encode($publicKeyCredentialSource->getTrustPath()->jsonSerialize()));
		$publicKeyCredentialEntity->setAaguid($publicKeyCredentialSource->getAaguid()->toString());
		$publicKeyCredentialEntity->setCredentialPublicKey(base64_encode($publicKeyCredentialSource->getCredentialPublicKey()));
		$publicKeyCredentialEntity->setUserHandle($publicKeyCredentialSource->getUserHandle());
		$publicKeyCredentialEntity->setCounter($publicKeyCredentialSource->getCounter());
		$publicKeyCredentialEntity->setCreatedAt($ctime);

		return $publicKeyCredentialEntity;
	}

	public function toPublicKeyCredentialSource(): PublicKeyCredentialSource {
		return new PublicKeyCredentialSource(
			base64_decode($this->publicKeyCredentialId),
			$this->type,
			json_decode($this->transports),
			$this->attestationType,
			TrustPathLoader::loadTrustPath((array)json_decode($this->trustPath)),
			Uuid::fromString($this->aaguid),
			base64_decode($this->credentialPublicKey),
			$this->userHandle,
			$this->counter
		);
	}
}
