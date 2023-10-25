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
		<!-- eslint-disable vue/no-v-html -->
		<div :class="{ centered, 'markdown-body': githubMarkdown }"
			v-html="note.text" />
		<!-- eslint-enable -->
		<span class="datetime">{{ formatDateTime(note.createdAt) }}</span>
		<NcModal v-if="modal"
			size="normal"
			@close="closeModal">
			<div class="modal__content">
				<h3>{{ t('login_notes', 'Edit note') }}</h3>
				<EditNote :available-pages="availablePages"
					:note="note"
					@update="updateNote"
					@cancel="closeModal" />
			</div>
		</NcModal>
		<NcActions>
			<NcActionButton icon="icon-rename" @click="showModal">
				{{ t('login_notes', 'Edit') }}
			</NcActionButton>
			<NcActionButton icon="icon-delete" @click="$emit('delete', note.id)">
				{{ t('login_notes', 'Delete') }}
			</NcActionButton>
		</NcActions>
	</div>
</template>
<script>
import moment from '@nextcloud/moment'
import { NcActions, NcActionButton, NcModal } from '@nextcloud/vue'
import EditNote from './EditNote.vue'
export default {
	name: 'ViewNote',
	components: {
		NcActions, NcActionButton, EditNote, NcModal,
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
			modal: false,
		}
	},
	computed: {
		centered() {
			return this.alignment === 'centered'
		},
	},
	methods: {
		formatDateTime(createdAt) {
			return moment(createdAt * 1000).format('LLL')
		},
		showModal() {
			this.modal = true
		},
		closeModal() {
			this.modal = false
		},
		updateNote(e) {
			this.$emit('update', e)
			this.closeModal()
		},
	},
}
</script>
