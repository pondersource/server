<?php
/**
 * @copyright Copyright (c) 2021 Thomas Citharel <nextcloud@tcit.fr>
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

namespace OCA\LoginNotes\Listeners;

use OCA\LoginNotes\AppInfo\Application;
use OCA\LoginNotes\Manager;
use OCA\LoginNotes\Model\Note;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

/**
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class InjectNote implements IEventListener
{
	private IConfig $config;
	private IRequest $request;
	private IInitialState $initialState;
	private Manager $manager;
	public function __construct(IRequest $request, IConfig $config, IInitialState $initialState, Manager $manager)
	{
		$this->request = $request;
		$this->config = $config;
		$this->initialState = $initialState;
		$this->manager = $manager;
	}


	/**
	 * @throws Exception
	 * @throws \JsonException
	 */
	public function handle(Event $event): void
	{
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}
		$url = $this->request->getRequestUri();
		$match = $this->matches($url);
		if ($match !== false) {
			$notes = array_values(array_filter($this->manager->getNotes(), static function (Note $note) use ($match) {
				$pages = ($note->getPagesEnabled() ?? '{"login":true}');
				/** @var array $pagesArray */
				$pagesArray = json_decode($pages, true, 512, JSON_THROW_ON_ERROR);
				return isset($pagesArray[$match]) && $pagesArray[$match] === true;
			}));
			$isCentered = $this->config->getAppValue(Application::APP_NAME, 'centered', 'no') === 'yes';
			$githubMarkdown = $this->config->getAppValue(Application::APP_NAME, 'github_markdown', 'no') === 'yes';
			$this->initialState->provideInitialState('centered', $isCentered);
			$this->initialState->provideInitialState('github_markdown', $githubMarkdown);
			$this->initialState->provideInitialState(
				'notes',
				$notes
			);
			Util::addScript(Application::APP_NAME, 'login_notes-main');
			Util::addStyle(Application::APP_NAME, 'markdown');
		}
	}

	/**
	 * @param string $url
	 * @return false|string
	 */
	private function matches(string $url)
	{
		$matches2FA = $this->matches2FA($url);
		if (count($matches2FA) > 1) {
			return $matches2FA[1];
		}
		if ($this->matches2FAChallenge($url)) {
			return 'challenge';
		}
		if ($this->matchesSAML($url)) {
			return 'saml';
		}
		if ($this->matchesLogin($url)) {
			return 'login';
		}
		return false;
	}

	private function matchesLogin(string $url): bool
	{
		return preg_match('%/login(\?.+)?$%m', $url) === 1;
	}

	private function matchesSAML(string $url): bool
	{
		return preg_match('%/saml/selectUserBackEnd(\?.+)?$%m', $url) === 1;
	}

	private function matches2FAChallenge(string $url): bool
	{
		return preg_match('/\/login\/selectchallenge(\?.+)?$/', $url) === 1;
	}

	/**
	 * @param string $url
	 * @return string[]
	 * @psalm-return array<array-key, string>
	 */
	private function matches2FA(string $url): array
	{
		preg_match('/\/login\/challenge\/(.+)*(\?.+)?$/', $url, $matches);
		return $matches;
	}
}
