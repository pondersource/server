<?php
/**
 * @copyright 2020 Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LoginNotes\Settings;

use OCA\LoginNotes\AppInfo\Application;
use OCA\LoginNotes\Manager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings
{
	private Manager $manager;
	private IInitialState $initialState;
	private IConfig $config;

	public function __construct(Manager $manager, IInitialState $initialState, IConfig $config)
	{
		$this->manager = $manager;
		$this->initialState = $initialState;
		$this->config = $config;
	}

	/**
	 * @throws Exception
	 */
	public function getForm(): TemplateResponse
	{
		$isCentered = $this->config->getAppValue(Application::APP_NAME, 'centered', 'no') === 'yes';
		$githubMarkdown = $this->config->getAppValue(Application::APP_NAME, 'github_markdown', 'no') === 'yes';
		$twoFATotpEnabled = class_exists(\OCA\TwoFactorTOTP\AppInfo\Application::class);
		$samlEnabled = class_exists(\OCA\User_SAML\AppInfo\Application::class);
		$twoFAU2FEnabled = class_exists(\OCA\TwoFactorU2F\AppInfo\Application::class);
		$twoFAEmailEnabled = class_exists(\OCA\TwoFactorEmail\AppInfo\Application::class);
		$twoFANotificationEnabled = class_exists(\OCA\TwoFactorNextcloudNotification\AppInfo\Application::class);
		$this->initialState->provideInitialState('centered', $isCentered);
		$this->initialState->provideInitialState('github_markdown', $githubMarkdown);
		$this->initialState->provideInitialState('notes', $this->manager->getNotes());
		$this->initialState->provideInitialState('pages', ['totp' => $twoFATotpEnabled, 'saml' => $samlEnabled, 'u2f' => $twoFAU2FEnabled, 'email' => $twoFAEmailEnabled, 'twofactor_nextcloud_notification' => $twoFANotificationEnabled]);

		Util::addScript(Application::APP_NAME, 'login_notes-settings');

		return new TemplateResponse('login_notes', 'admin');
	}

	public function getSection(): string
	{
		return 'additional';
	}

	public function getPriority(): int
	{
		return 10;
	}
}
