// Import Uncanny Owl icon
import {
	UncannyOwlIconColor
} from '../components/icons';

// Import Editor components
import {
	ContentPlaceholder,
	ContentReady,
	Header,
	Description,
	ActionButtons,
	Notice,
	LoadingContent,
	ContentExplorer
} from './components/editor';

// Import Sidebar filters
import './components/sidebar';

//  Import CSS.
import './css/style.scss';
import './css/editor.scss';

// Import TinCanny Uploader.
import TincannyZipUploader from 'tincanny-zip-uploader/src/components/tincanny-zip-uploader';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { Fragment } = wp.element;

registerBlockType( 'tincanny/content', {
	title: vc_snc_data_obj.i18n.tinCannyContent,

	description: vc_snc_data_obj.i18n.uploadEmbedTinCanny,

	icon: UncannyOwlIconColor,

	category: 'uncanny-learndash-reporting',

	keywords: [
		'Uncanny Owl',
	],

	supports: {
		html: false
	},

	attributes: {
		status: {
			type:    'string',
			default: 'start'
		},
		contentId: {
			type:    'string',
			default: ''
		},
		contentTitle: {
			type:    'string',
			default: ''
		},
		contentUrl: {
			type:    'string',
			default: ''
		}
	},

	edit({ className, attributes, setAttributes }){

		// Create variable to save all the components we're going
		// to show in the editor
		let editorComponents = [];

		if (// Check if:
			// the user was uploading content but refreshed the page
			( attributes.status == 'uploading-content' && ! isDefined( attributes.file ) )
			// the user was loading the library but refreshed the page
			|| ( attributes.status == 'fetching-content' && ! isDefined( attributes.fetchInProgress ) )
			// the user was selecting a content but then refreshed the page
			|| ( attributes.status == 'content-library-ready' && ! isDefined( attributes.library ) )
			// the user was selecting a launcher in the unsupported content
			// but refreshed the page
			|| ( attributes.status == 'not-supported' && ! isDefined( attributes.unsupportedContent ) )
			// The user selected a file in the launcher and the AJAX request started
			// but before finishing the user refreshed the page
			|| ( attributes.status == 'not-supported-selected' && ! isDefined( attributes.unsupportedContentSelectedFile ) )
		){
			// Then start again
			attributes.status = 'start';
		}

		// Check if we have content or we have to show the placeholder
		if ( attributes.status == 'has-valid-content' ){
			// Add ContentReady component
			editorComponents.push((
				<ContentReady
					contentName={ attributes.contentTitle }
					data={ attributes }
				/>
			));
		}
		else {
			// Create array we're we are going to save all the
			// components to show on the placeholder, depending of our data
			let placeholderComponents = [];

			// Initial status, the user didn't select a file yet
			if ( attributes.status == 'start' ){
				// Check for notices
				let notices = [];
				if ( isDefined( attributes.notice ) ){
					if ( attributes.notice.type == 'error' ){
						notices.push((
							<Notice
								type="error"
								content={ attributes.notice.message }
							/>
						));
					}
				}

				// Add components
				placeholderComponents.push((
					<Fragment>
						<Header/>
						<Description
							content={ vc_snc_data_obj.i18n.embedTinCanny }
						/>
						<ActionButtons
							onUpload={( event ) => {
								setAttributes({
									status: 'uploading-content',
									file:   event.target.files[0]
								});
							}}
							onClickSelect={() => {
								setAttributes({
									status:          'fetching-content',
									fetchInProgress: true
								});
							}}
						/>
						{ notices }
					</Fragment>
				));
			}
			// The user used "Upload", we have to upload attributes.file
			else if ( attributes.status == 'uploading-content' ){
				tincannyZipUploadData.debug = tincannyZipUploadData.debug && parseInt( tincannyZipUploadData.debug ) ? 1 : 0;
				// Set the settings config.
				tincannyZipUploadData.fullZipUpload = 1;
				if ( attributes.hasOwnProperty( 'fullZipUpload' ) && ! attributes.fullZipUpload ) {
					tincannyZipUploadData.fullZipUpload = 0;
				}
				if ( tincannyZipUploadData.debug ) {
					console.log( 'Tin Canny Content', {
						fullZipUpload: tincannyZipUploadData.fullZipUpload,
						attributes : attributes,
						tincannyZipUploadData : tincannyZipUploadData, 
					} );
				}
				// Add container for the uploader component.
				placeholderComponents.push((
					<Fragment>
						<Header/>
						<TincannyZipUploader
							file={attributes.file}
							config={tincannyZipUploadData}
							onUpdate={ ( response ) => {
								const action = response.action;
								if ( tincannyZipUploadData.debug ) {
									console.log( 'Tin Canny Content TincannyZipUploader onUpdate',{
										action:action,
										response:response
									});
								}
								if( action === 'upload-started-tincanny-zip' ) {
									return;
								}
								if ( response.success ) {
									setAttributes({
										status:       'has-valid-content',
										contentId:    String( response.id ),
										contentTitle: response.title
									});
								} else {
									// Error. Show custom message or "Try again"
									let errorMessage = __( 'Something went wrong. Please, try again' );
									if ( response.hasOwnProperty( 'error' ) ) {
										errorMessage = response.error;
									} else if ( response.hasOwnProperty( 'message' ) ) {
										errorMessage = response.message;
									}
									setAttributes({
										status:      'start',
										notice: {
											type:    'error',
											message: response.error ? response.error : vc_snc_data_obj.i18n.somethingWrongWrong
										}
									});
								}
							} }
						/>
					</Fragment>
				));
			}
			// The user clicked "Select from library", we have to get the list of content
			else if ( attributes.status == 'fetching-content' ){
				placeholderComponents.push((
					<LoadingContent
						text={ vc_snc_data_obj.i18n.loadingLibrary }
					/>
				));

				// Get list of content
				let formData = new FormData();

				formData.append( 'security', vc_snc_data_obj.ajax_nonce );
				formData.append( 'action',   'vc_snc_data' );

				fetch( vc_snc_data_obj.ajaxurl, {
					method: 'POST',
					body:   formData,
				})
				.then( response => response.json() )
				.then(
					( result ) => {
						// Check if the result is an array
						if ( result.constructor == Array ){
							if ( result.length > 0 ){
								// Prepare library
								let library = result.map(( item, index ) => {
									return {
										id:          String( item.ID ),
										title:       item.file_name,
										titleSearch: item.file_name.toLowerCase().replace( /\s/g, '' ),
										url:         item.url
									}
								})

								// Set attributes and go to next step
								setAttributes({
									status:  'content-library-ready',
									library: library
								});
							}
							else {
								setAttributes({
									status:      'start',
									notice: {
										type:    'error',
										message: vc_snc_data_obj.i18n.weDidntFindContent
									}
								});
							}
						}
						else {
							// Error. Show "Try again"
							setAttributes({
								status:      'start',
								notice: {
									type:    'error',
									message: vc_snc_data_obj.i18n.somethingWrongWrong
								}
							});
						}
					},
					( error ) => {
						// Error. Show "Try again"
						setAttributes({
							status:      'start',
							notice: {
								type:    'error',
								message: vc_snc_data_obj.i18n.somethingWrongWrong
							}
						});
					}
				);
			}
			// The list is ready, show it
			else if ( attributes.status == 'content-library-ready' ){
				placeholderComponents.push((
					<Fragment>
						<Header/>
						<ContentExplorer
							searchQuery={ attributes.searchQuery }
							onSearch={( searchQuery ) => {
								setAttributes({
									searchQuery: searchQuery
								});
							}}
							files={ attributes.library }
							onClickDo={( contentItem ) => {
								// The user selected an item
								setAttributes({
									status:       'has-valid-content',
									contentId:    contentItem.id,
									contentTitle: contentItem.title,
									contentUrl:   contentItem.url
								});
							}}
							onCancelDo={() => {
								// Delete this and go to the first step
								setAttributes({
									status:       'start',
								});
							}}
						/>
					</Fragment>
				));
			}

			// Add placeholder and his components
			editorComponents.push((
				<ContentPlaceholder>
					{ placeholderComponents }
				</ContentPlaceholder>
			));
		}

		return (
			<div className={ className }>
				{ editorComponents }
			</div>
		);
	},

	save({ className, attributes }){
		// We're going to render this block using PHP
		// Return null
		return null;
	},
});

export const isDefined = ( variable ) => {
	return variable !== undefined && variable !== null;
}