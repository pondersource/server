<!--
 - @copyright Copyright (c) 2020 Thomas Citharel <nextcloud@tcit.fr>
 -
 - @author Thomas Citharel <nextcloud@tcit.fr>
 -
 - @license GNU AGPL version 3 or any later version
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<div id="login_notes_settings" class="section">
		<h2>{{ t('login_notes', 'Notes on login screen') }}</h2>
		<form class="alignment">
			<h3>{{ t('login_notes', 'Text alignment') }}</h3>
			<fieldset>
				<p>
					<input id="login_notes_aligned"
						v-model="alignment"
						type="radio"
						name="alignment"
						class="radio"
						value="aligned">
					<label for="login_notes_aligned">
						{{ t('login_notes', 'Aligned') }}<br>
						<em>{{ t('login_notes', 'Note content will be aligned to left (or right with RTL languages).') }}</em>
					</label>
				</p>
				<p>
					<input id="login_notes_centered"
						v-model="alignment"
						type="radio"
						name="alignment"
						class="radio"
						value="centered">
					<label for="login_notes_centered">
						{{ t('login_notes', 'Centered') }}<br>
						<em>{{ t('login_notes', 'Note content will be centered.') }}</em>
					</label>
				</p>
			</fieldset>

			<h3>{{ t('login_notes', 'Github Markdown Styles') }}</h3>
			<p>
				<input id="login_notes_github_markdown"
					v-model="githubMarkdown"
					type="checkbox"
					name="alignment"
					class="checkbox"
					:value="false">
				<label for="login_notes_github_markdown">
					{{ t('login_notes', 'Github Markdown Styles') }}<br>
					<em>{{ t('login_notes', 'The note will be styled with Github Markdown Style.') }}</em>
				</label>
			</p>
		</form>
		<h3>{{ t('login_notes', 'Notes list') }}</h3>
		<p class="settings-hint">
			{{ t('login_notes', 'These notes will be shown on the login screen.') }}
		</p>
		<div v-if="notes.length > 0" class="notes">
			<ul>
				<transition-group name="fade" tag="li">
					<ViewNote v-for="note in notes"
						:key="note.id"
						:note="note"
						:alignment="alignment"
						:github-markdown="githubMarkdown"
						:available-pages="pages"
						:has-at-least-two2-f-a-providers="hasAtLeastTwo2FAProviders"
						@delete="deleteNote"
						@update="updateNote" />
				</transition-group>
			</ul>
			<button type="button" @click="showModal">
				{{ t('login_notes', 'Add new note') }}
			</button>
		</div>
		<NcEmptyContent v-else>
			{{ t('login_notes', 'No notes yet') }}
			<template #action>
				<NcButton type="primary" @click="showModal">
					{{ t('login_notes', 'Add new note') }}
				</NcButton>
			</template>
			<template #title>
				{{ t('login_notes', 'No notes yet') }}
			</template>
			<template #icon>
				<NoteEdit />
			</template>
		</NcEmptyContent>
		<NcModal v-if="modal" size="large" @close="closeModal">
			<div class="modal__content">
				<h3>{{ t('login_notes', 'Create note') }}</h3>
				<EditNote :available-pages="pages"
					@update="updateNote"
					@create="createNote"
					@cancel="closeModal" />
			</div>
		</NcModal>
	</div>
</template>
<script>
import { loadState } from '@nextcloud/initial-state'
import EditNote from '../components/EditNote.vue'
import ViewNote from '../components/ViewNote.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { set } from 'vue'
import { NcModal, NcEmptyContent, NcButton } from '@nextcloud/vue'
import NoteEdit from 'vue-material-design-icons/NoteEdit.vue'

export default {
	name: 'Settings',
	components: { ViewNote, EditNote, NcModal, NcEmptyContent, NcButton, NoteEdit },
	data() {
		return {
			notes: loadState('login_notes', 'notes'),
			alignment: loadState('login_notes', 'centered') === true ? 'centered' : 'aligned',
			githubMarkdown: loadState('login_notes', 'github_markdown') === true,
			pages: loadState('login_notes', 'pages'),
			newNoteData: { text: '', created_at: 0, pages: { login: true } },
			modal: false,
		}
	},
	computed: {
		hasAtLeastTwo2FAProviders() {
			return [this.pages.u2f, this.pages.totp, this.pages.twofactor_nextcloud_notification].filter(page => page === true).length >= 2
		},
	},
	watch: {
		async alignment(alignment) {
			await axios.post(generateUrl('/apps/login_notes/settings'), {
				centered: alignment === 'centered',
			})
		},
		async githubMarkdown(githubMarkdown) {
			await axios.post(generateUrl('/apps/login_notes/settings'), {
				github_markdown: githubMarkdown,
			})
		},
	},
	methods: {
		async createNote({ text, pages }) {
			const note = await axios.post(generateUrl('/apps/login_notes/notes'), {
				text,
				pages,
			})
			this.notes.push(note.data)
			this.closeModal()
		},
		async deleteNote(noteId) {
			try {
				await axios.delete(generateUrl(`/apps/login_notes/notes/${noteId}`))
				this.notes = this.notes.filter(note => note.id !== noteId)
			} catch (e) {
				console.error(e)
			}
		},
		async updateNote({ text, id, pages }) {
			try {
				const { data } = await axios.patch(generateUrl(`/apps/login_notes/notes/${id}`), { text, pages })
				set(this.notes, this.notes.findIndex(note => note.id === id), data)
				this.closeModal()
			} catch (e) {
				console.error(e)
			}
		},
		showModal() {
			this.modal = true
		},
		closeModal() {
			this.modal = false
		},
	},
}
</script>
