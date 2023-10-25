<?php
/**
 * @copyright Copyright (c) 2021 Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
 * @author René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Cron;

use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;

use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class UserDeletedJob extends QueuedJob {
	public function __construct(
		private CommentMapper $commentMapper,
		private ISecureRandom $secureRandom,
		protected ITimeFactory $time,
		private LoggerInterface $logger,
		private LogMapper $logMapper,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private PreferencesMapper $preferencesMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private VoteMapper $voteMapper,
	) {
		parent::__construct($time);
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$userId = $argument['userId'];
		$this->logger->info('Deleting polls for deleted user id {user}', [
			'user' => $userId
		]);

		$replacementName = 'deleted_' . $this->secureRandom->generate(
			8,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->pollMapper->deleteByUserId($userId);
		$this->logMapper->deleteByUserId($userId);
		$this->shareMapper->deleteByIdAndType($userId, Share::TYPE_USER);
		$this->preferencesMapper->deleteByUserId($userId);
		$this->subscriptionMapper->deleteByUserId($userId);
		$this->commentMapper->renameUserId($userId, $replacementName);
		$this->optionMapper->renameUserId($userId, $replacementName);
		$this->voteMapper->renameUserId($userId, $replacementName);
	}
}
