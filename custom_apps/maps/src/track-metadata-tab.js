/**
 * @copyright Copyright (c) 2022 Arne Hamann <git@arne.email>
 *
 * @author Arne Hamann <git@arne.email>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import TrackMetadataTab from './views/TrackMetadataTab.vue'

Vue.prototype.t = t
Vue.prototype.n = n

// Init Tracks tab component
const View = Vue.extend(TrackMetadataTab)

// Init Maps Track tab component
let TabInstance = null
const trackMetadataTab = new OCA.Files.Sidebar.Tab({
	id: 'maps-track-metadata',
	name: t('maps', 'Metadata'),
	icon: 'icon-info',

	async mount(el, fileInfo, context) {
		if (TabInstance) {
			TabInstance.$destroy()
		}
		TabInstance = new View({
			// Better integration with vue parent component
			parent: context,
		})
		// Only mount after we have all the info we need
		await TabInstance.update(fileInfo.id)
		TabInstance.$mount(el)
	},
	update(fileInfo) {
		TabInstance.update(fileInfo.id)
	},
	destroy() {
		TabInstance.$destroy()
		TabInstance = null
	},
	enabled(fileInfo) {
		return ['application/gpx+xml'].includes(fileInfo.mimetype)
	},
	scrollBottomReached() {
		TabInstance.onScrollBottomReached()
	},
})

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(trackMetadataTab)
	}
})
