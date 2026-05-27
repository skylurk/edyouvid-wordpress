import './sidebar.js';

import {
	UncannyOwlIconColor
} from '../components/icons';

import {
	GroupsPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;


registerBlockType('uncanny-learndash-groups/uo-groups-edit-group', {
	title: __('Edit Group Wizard', 'uncanny-learndash-groups'),

	description: __('Edit LearnDash groups from the front end.', 'uncanny-learndash-groups'),

	icon: UncannyOwlIconColor,

	category: 'uncanny-learndash-groups',

	keywords: [
		__('Uncanny Owl - Groups Plugin', 'uncanny-learndash-groups'),
	],

	supports: {
		html: false
	},

	attributes: {
		groupName: {
			type: 'string',
			default: 'show'
		},
		groupParent: {
			type: 'string',
			default: 'show'
		},
		totalSeats: {
			type: 'string',
			default: 'show'
		},
		groupCourses: {
			type: 'string',
			default: 'show'
		},
		groupImage: {
			type: 'string',
			default: 'show'
		},
		category: {
			type: 'string',
			default: ''
		},
		courseCategory: {
			type: 'string',
			default: ''
		},
	},

	edit({className, attributes, setAttributes}) {
		return (
			<div className={className}>
				<GroupsPlaceholder>
					{__('Edit Group Wizard', 'uncanny-learndash-groups')}
				</GroupsPlaceholder>
			</div>
		);
	},

	save({className, attributes}) {
		// We're going to render this block using PHP
		// Return null
		return null;
	},
});

