<?php
/**
 * @copyright Copyright (c) 2018 Marius Blüm <marius@nextcloud.com>
 *
 * @author Marius Blüm <marius@nextcloud.com>
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

namespace OCA\DriveEmailTemplate\Migration;

use OCA\DriveEmailTemplate\EmailTemplate;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SetEmailTemplate implements IRepairStep {
	/** @var IConfig */
	protected $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName() {
		return 'Set the custom email template';
	}

	public function run(IOutput $output) {
		$this->config->setSystemValue('mail_template_class', EMailTemplate::class);
	}
}
