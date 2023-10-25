<?php
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
namespace OCA\LoginNotes\Controller;

use OCA\LoginNotes\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class SettingController extends Controller
{
	private IConfig $config;

	public function __construct(string $AppName, IRequest $request, IConfig $config)
	{
		parent::__construct($AppName, $request);
		$this->config = $config;
	}

	/**
	 * @param bool $centered
	 * @param bool $github_markdown
	 * @return DataResponse
	 */
	public function set(?bool $centered, ?bool $github_markdown): DataResponse
	{
		if ($centered !== null) {
			$this->config->setAppValue(Application::APP_NAME, 'centered', $centered ? 'yes' : 'no');
		}
		if ($github_markdown !== null) {
			$this->config->setAppValue(Application::APP_NAME, 'github_markdown', $github_markdown ? 'yes' : 'no');
		}
		return new DataResponse([], Http::STATUS_OK);
	}
}
