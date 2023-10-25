<?php
/**
 * @copyright 2018, Marius Blüm <marius@nextcloud.com>
 *
 * @author Marius Blüm <marius@nextcloud.com>
 * @author Richard Freitag <freitag@sunet.se>
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

namespace OCA\DriveEmailTemplate;

use OC\Mail\EMailTemplate as ParentTemplate;

class EMailTemplate extends ParentTemplate {

	public function getTextLeft(){
	       return \OC::$server->getConfig()->getSystemValue('drive_email_template_text_left', 'Gå till Sunet Drive');
	}
	public function getPlainTextLeft(){
	       return \OC::$server->getConfig()->getSystemValue('drive_email_template_plain_text_left', 'Gå till Sunet Drive');
	}
	public function getUrlLeft(){
	       return \OC::$server->getConfig()->getSystemValue('drive_email_template_url_left', 'https://drive.sunet.se/');
	}

	/**
	 * the following method overwrites the add button group method and
	 * manipulates the result for the welcome email to only include one button
	 */
	public function addBodyButtonGroup(
		string $textLeft, string $urlLeft,
		string $textRight, string $urlRight,
		string $plainTextLeft = '',
		string $plainTextRight = '') {

		// for the welcome email we omit the left button ("Install client") and only show the button that links to the instance
		if ($this->emailId === 'settings.Welcome') {
			$urlLeft = $this->getUrlLeft();
			$textLeft = $this->getTextLeft();
			$plainTextLeft = $this->getPlainTextLeft();
			parent::addBodyButton($textLeft, $urlLeft, $plainTextLeft);
			return;
		}
		parent::addBodyButtonGroup($textLeft, $urlLeft, $textRight, $urlRight, $plainTextLeft, $plainTextRight);
	}

	/**
	 * Adds a logo and a text to the footer. <br> in the text will be replaced by new lines in the plain text email
	 *
	 * This method completely overwrites the default behaviour.
	 */
	
	/**
	public function addFooter(string $text = '', ?string $lang = null) {
		if ($this->footerAdded) {
			return;
		}
		$this->footerAdded = true;
		$this->ensureBodyIsClosed();
		$this->htmlBody .= 'Sunet Drive<br>';
		$this->htmlBody .= $this->tail;
		$this->plainBody .= PHP_EOL . '-- ' . PHP_EOL;
		$this->plainBody .= str_replace('<br>', PHP_EOL, $text);
	}*/
}
