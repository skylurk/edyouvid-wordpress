/**
 * WordPress dependencies
 */
const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

/**
 * Internal dependencies
 */
import { UncannyOwlIconColor } from '../components/icons';
import { UcecPlaceholder } from '../components/editor';
import CeuCourseReportSidebar from './sidebar';

/**
 * Register block
 */
registerBlockType( 'uncanny-ceu/uo-ceu-course-report', {
	title: __( 'Continuing Education Report', 'uncanny-ceu' ),
	description: __( 'Displays a front end report that allows anyone with access to the page to look up course completions and earned credits for any user on the site.', 'uncanny-ceu' ),
	icon: UncannyOwlIconColor,
	category: 'uncanny-ceu',
	keywords: [
		__( 'Uncanny Owl', 'uncanny-ceu' ),
	],
	supports: {
		html: false
	},
	attributes: {
		enhanced: {
			type: 'string',
			default: null,
		},
		columns: {
			type: 'string',
			default: null,
		},
		mode: {
			type: 'string',
			default: null,
		},
		add: {
			type: 'string',
			default: null,
		},
		edit: {
			type: 'string',
			default: null,
		},
		delete: {
			type: 'string',
			default: null,
		},
	},
	
	edit({ className, attributes, setAttributes }){
		return (
			<div className={className}>
				<UcecPlaceholder>
					{ __( 'Continuing Education Report', 'uncanny-ceu' ) }
				</UcecPlaceholder>
				<CeuCourseReportSidebar
					attributes={attributes} 
					setAttributes={setAttributes} 
				/>
			</div>
		);
	},
	
	save({ className, attributes }){
		// We're going to render this block using PHP
		// Return null
		return null;
	},
});
