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

use OCA\LoginNotes\Manager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class NoteController extends Controller
{
	private Manager $manager;

	public function __construct(string $AppName, IRequest $request, Manager $manager)
	{
		parent::__construct($AppName, $request);
		$this->manager = $manager;
	}

	/**
	 * @param string $text
	 * @param array $pages
	 * @return DataResponse
	 * @throws Exception|\JsonException
	 */
	public function create(string $text, array $pages): DataResponse
	{
		$note = $this->manager->create($text, $pages);
		return new DataResponse($note);
	}

	/**
	 * @param int $id
	 * @param string $text
	 * @param array $pages
	 * @return DataResponse
	 * @throws \JsonException
	 */
	public function update(int $id, string $text, array $pages): DataResponse
	{
		try {
			$note = $this->manager->update($id, $text, $pages);
			return new DataResponse($note);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (MultipleObjectsReturnedException|Exception $e) {
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param int $id
	 * @return DataResponse
	 */
	public function destroy(int $id): DataResponse
	{
		try {
			$note = $this->manager->getById($id);
			$this->manager->delete($note);
			return new DataResponse($note);
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
