// Import Uncanny Owl icon
import {
	UncannyOwlIconColor
} from '../components/icons';

import {
    TinCannyPlaceholder
} from '../components/editor';

import './sidebar.js';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

registerBlockType( 'tincanny/group-quiz-report', {
	title: vc_snc_data_obj.i18n.tinCannyGroupQuizReportBlockTitle,

	description: vc_snc_data_obj.i18n.tinCannyGroupQuizReportBlockDescription,

	icon: UncannyOwlIconColor,

	category: 'uncanny-learndash-reporting',

	keywords: [
		'Uncanny Owl',
	],

	supports: {
		html: false
	},

	attributes: {
		'user_report_url': {
            type: 'string',
            default: ''
        },
	},

	edit({ className, attributes, setAttributes }){
		return (
			<div className={ className }>
				<TinCannyPlaceholder>
					{ vc_snc_data_obj.i18n.tinCannyGroupQuizReportBlockTitle }
				</TinCannyPlaceholder>
			</div>
		);
	},

	save({ className, attributes }){
		// We're going to render this block using PHP
		// Return null
		return null;
	},
});