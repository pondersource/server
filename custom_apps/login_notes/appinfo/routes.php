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

return ['routes' => [
	['name' => 'note#create', 'url' => '/notes', 'verb' => 'POST'],
	['name' => 'note#destroy', 'url' => '/notes/{id}', 'verb' => 'DELETE'],
	['name' => 'note#update', 'url' => '/notes/{id}', 'verb' => 'PATCH'],
	['name' => 'setting#set', 'url' => '/settings', 'verb' => 'POST']
]];
