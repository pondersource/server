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
	<div class="note">
		<form class="form-edit" @submit.prevent="createOrUpdateNote">
			<label>
				<textarea v-model="updatedNote.text" :placeholder="t('login_notes', 'Enter the text for the updated login note here. Markdown supported.')" />
			</label>
			<div v-show="hasOtherPages">
				<h4>{{ t('login_notes', 'Where to show this note?') }}</h4>
				<p>
					<input :id="`login_note${note.id}_pages_login`"
						v-model="updatedNote.pages.login"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_login`">
						{{ t('login_notes', 'Show on login page') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the login page') }}</em>
					</label>
				</p>
				<p v-if="availablePages.saml">
					<input :id="`login_note${note.id}_pages_saml`"
						v-model="updatedNote.pages.saml"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_saml`">
						{{ t('login_notes', 'Show on SAML login option selection') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the page which asks the user which login option to pick when SAML is used') }}</em>
					</label>
				</p>
				<p v-if="availablePages.totp">
					<input :id="`login_note${note.id}_pages_totp`"
						v-model="updatedNote.pages.totp"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_totp`">
						{{ t('login_notes', 'Show on 2FA TOTP page') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the 2FA TOTP page, when asking for the TOTP code') }}</em>
					</label>
				</p>
				<p v-if="availablePages.u2f">
					<input :id="`login_note${note.id}_pages_u2f`"
						v-model="updatedNote.pages.u2f"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_u2f`">
						{{ t('login_notes', 'Show on 2FA U2F page') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the 2FA U2F page, when asking for the code from the U2F device') }}</em>
					</label>
				</p>
				<p v-if="availablePages.twofactor_nextcloud_notification">
					<input :id="`login_note${note.id}_pages_twofactor_nextcloud_notification`"
						v-model="updatedNote.pages.twofactor_nextcloud_notification"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_twofactor_nextcloud_notification`">
						{{ t('login_notes', 'Show on 2FA Nextcloud Notification page') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the 2FA Nextcloud Notification page, when asking to confirm the connection on another device') }}</em>
					</label>
				</p>
				<p v-if="hasAtLeastTwo2FAProviders">
					<input :id="`login_note${note.id}_pages_challenge`"
						v-model="updatedNote.pages.challenge"
						type="checkbox"
						name="login_notes_pages"
						class="checkbox">
					<label :for="`login_note${note.id}_pages_challenge`">
						{{ t('login_notes', 'Show on 2FA challenge selection page') }}<br>
						<em>{{ t('login_notes', 'The note will be shown on the page which asks the user which 2FA method to use for the challenge') }}</em>
					</label>
				</p>
			</div>
			<div class="form-edit-action-buttons">
				<NcButton v-if="updateMode" native-type="submit" type="primary">
					{{ t('login_notes', 'Update note') }}
				</NcButton>
				<NcButton v-else native-type="submit" type="primary">
					{{ t('login_notes', 'Create note') }}
				</NcButton>
				<NcButton native-type="button" @click="$emit('cancel')">
					{{ t('login_notes', 'Cancel') }}
				</NcButton>
			</div>
		</form>
	</div>
</template>
<script>
import moment from '@nextcloud/moment'
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'EditNote',
	components: {
		NcButton,
	},
	props: {
		note: {
			type: Object,
			required: false,
			default: () => ({ created_at: 0, pages: [], text: '', rawText: '' }),
		},
		alignment: {
			type: String,
			required: false,
			default: 'aligned',
		},
		githubMarkdown: {
			type: Boolean,
			required: false,
			default: false,
		},
		availablePages: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			updateMode: this.note.rawText !== '',
			updatedNote: { text: this.note.rawText, pages: this.note.pagesEnabled || { login: true } },
		}
	},
	computed: {
		centered() {
			return this.alignment === 'centered'
		},
		hasOtherPages() {
			return Object.values(this.availablePages).some(page => page === true)
		},
		hasAtLeastTwo2FAProviders() {
			return [this.availablePages.u2f, this.availablePages.totp, this.availablePages.twofactor_nextcloud_notification].filter(page => page === true).length >= 2
		},
	},
	methods: {
		formatDateTime(createdAt) {
			return moment(createdAt * 1000).format('LLL')
		},
		deleteNote(noteId) {
			this.$emit('delete', noteId)
		},
		createOrUpdateNote() {
			if (this.updateMode) {
				this.$emit('update', { text: this.updatedNote.text, id: this.note.id, pages: this.updatedNote.pages })
			} else {
				this.$emit('create', { text: this.updatedNote.text, pages: this.updatedNote.pages })
			}
		},
		cancel() {
			this.$emit('cancel', {})
		},
	},
}
</script>

<style scoped>
.form-edit-action-buttons {
	display: flex;
	gap: 1rem;
	margin:0.25rem auto;
}
</style>
