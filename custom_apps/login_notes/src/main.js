/**
 * @copyright Copyright (c) 2020 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { loadState } from '@nextcloud/initial-state'
const notes = loadState('login_notes', 'notes', [])
const centered = loadState('login_notes', 'centered', false)
const githubMarkdown = loadState('login_notes', 'github_markdown', false)
// insert warning
const submit = document.querySelector('.wrapper main')
notes.forEach(note => {
	const warning = `<div class="login-notes-rendered ${githubMarkdown ? ' markdown-body' : ''}${centered ? ' centered' : ''}" dir="auto">${note.text}</div>`
	submit.insertAdjacentHTML('afterend', warning)
})
