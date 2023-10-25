<?php
/**
 * @copyright Copyright (c) 2017 Thomas Citharel <nextcloud@tcit.fr>
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

namespace OCA\LoginNotes\AppInfo;

use OCA\LoginNotes\Listeners\InjectNote;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

include_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap
{
	public const APP_NAME = 'login_notes';

	public function __construct()
	{
		parent::__construct(self::APP_NAME);
	}

	public function register(IRegistrationContext $context): void
	{
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, InjectNote::class);
	}

	public function boot(IBootContext $context): void
	{
	}
}
