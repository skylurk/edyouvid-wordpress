/* eslint-disable no-undef, func-names, no-restricted-globals, no-use-before-define */

// Define global variable.
let tincannyMediaUpload;

// Define trigger upload function.
class TincannyMediaUploadClass {

	constructor( $, key) {
		this.$ = $;
		this.isIframe = window.self !== window.top;
		this.key = key || false;
		this.enableButton = true;
		this.ids = {
			parentId: 'snc-media_upload_file_wrap',
			uploadWrapperId: 'snc-media_upload_tincanny-uploader-wrapper',
			buttonWrapId: 'snc-media_upload_button_wrap',
			contentID: 'content_id',
			settingsWrapID: 'snc-media_upload_settings_wrap',
			fullZipUpload: 'snc-full_zip_upload',
			fullZipMax: 'snc-full_zip_upload__max'
		},
		this.config = window.tincannyZipUploadData || {};
		this.addEventListeners();
	}

	addEventListeners() {

		const app = this;

		// Add Listener for Uploader Events.
		document.addEventListener( "tincanny-zip-uploader-event", ( e ) => {
			const detail = e.detail || {};
			if ( ! detail.action ) {
				return;
			}

			if( parseInt( app.config.debug ) ) {
				console.log({
					action:'tincanny-zip-uploader-event-media.js',
					detail:detail,
				});
			}

			if ( detail.action === 'registered-tincanny-module' ) {
				if ( detail.success ) {
					app.handleUploadSuccess( detail );
				}
			}
			
			if ( detail.action === 'upload-started-tincanny-zip' ) {
				app.setConfigItem( 'directory', detail.directory );
			}

			if ( detail.action === 'cancel-tincanny-module-upload' ) {
				app.handleCancelledUpload( detail );
			}
		});

		// Add Click and Change Event Listeners.
		document.addEventListener('click', event => {
			if (event.target.matches('.snc-file_upload_button_wrapper button')) {
				if ( ! app.enableButton ) {
					return;
				}
				app.setSettings( app );
				const $parentWrapper = event.target.closest('.snc-file_upload_button_wrapper');
				const $fileInput = $parentWrapper.querySelector('input[type="file"]');
				$fileInput.click();
				$fileInput.onchange = ( e ) => {
					const $contentID = app.getElByID( app.ids.contentID );
					app.showLoading();
					app.triggerZipUploaderEvent( {
						action: 'upload-tincanny-zip',
						uploadWrapperID: app.ids.uploadWrapperId,
						file: e.target.files[0],
						contentID: $contentID ? $contentID.value : '',
						settings: app.getConfig('settings'),
					} );
					// Clear the file input value
    				e.target.value = null;
				}
			} else if (event.target.matches('#tab-snc-library a')) {
				app.maybeCleanUpAbortedUploads( app );
			} else if (event.target.matches('#' + this.ids.fullZipUpload) ) {
				app.setSettings( app );
			}
		});

		// Handle Aborted Uploads

		// Listen for Thickbox Close when code is triggered from iFrame or parent.
		const TBjQuery = app.isIframe ? window.parent.jQuery : app.$;
		TBjQuery("#TB_closeWindowButton, #TB_overlay").click(function(){
			app.maybeCleanUpAbortedUploads( app );
		});

		// Disable Escape Key.
		TBjQuery(document).on('keydown', function(event) {
			if (event.key === 'Escape' || event.keyCode === 27) {
				event.preventDefault();
				event.stopPropagation();
			}
		});
	}

	getElByID( id ) {
		const $el = document.getElementById(id);
		return $el && $el !== null ? $el : false;
	}

	showLoading() {
		this.enableButton = false;
		this.show( this.ids.uploadWrapperId );
		this.hide( this.ids.buttonWrapId );
		this.hide( this.ids.settingsWrapID );
	}

	hideLoading() {
		this.enableButton = true;
		this.show( this.ids.buttonWrapId );
		this.show( this.ids.settingsWrapID );
		this.hide( this.ids.uploadWrapperId );
		this.setConfigItem( 'directory', '' );
	}

	hide( id ) {
		const $el = this.getElByID(id);
		if ($el) {
			$el.style.display = 'none';
		}
	}

	show( id ) {
		const $el = this.getElByID(id);
		if ($el) {
			$el.style.display = 'block';
		}
	}

	setAttribute( selector, attribute, value ) {
		const $el = document.querySelector(selector);
		if ($el) {
			$el.setAttribute(attribute, value);
		}
	}

	triggerZipUploaderEvent( data ) {
		const event = new CustomEvent('tincanny-zip-uploader-event', { detail: data });
		document.dispatchEvent(event);
	}

	handleUploadSuccess( data ) {
		this.setConfigItem('directory', '');
		const $parent = this.getElByID( this.ids.parentId );
		if ( ! $parent ) {
			return;
		}

		const $noTab = $parent.querySelector('#no_tab');
		const $noRefresh = $parent.querySelector('#no_refresh');
		if ( $noTab ) {
			if ( $noRefresh ) {
				//self.parent.window.wp.tccmb_content($('#ele_id').val());
				self.parent.tb_remove();
			} else {
				window.parent.location = window.parent.location.href;
			}
		}

		// Handle Visual Composer.
		if ( this.key && this.key !== 'undefined' ) {
			this.setAttribute( '#vc_properties-panel .vc-snc-trigger input', 'value', data.id );
			this.setAttribute( '#vc_properties-panel .vc-snc-name input', 'value', data.title );
				trigger_vc_snc_mode();
		} else {
			const $embedInfo = document.querySelector('.snc-embed_information');
			if ( null !== $embedInfo ) {
				$embedInfo.style.display = 'block';
				this.hide( this.ids.parentId );
			}
			this.setAttribute( 'a.delete-media', 'data-item_id', data.id );
			this.setAttribute( 'input#item_id', 'value', data.id );
			this.setAttribute( 'input#item_title', 'value', data.title );
		}
	}

	handleCancelledUpload() {
		this.hideLoading();
	}

	maybeCleanUpAbortedUploads( app ) {
		const directory = app.getConfig( 'directory' );
		if ( directory === '' ) {
			return;
		}
		const config = window.top.tincannyZipUploadData;
		app.setConfigItem( 'directory', '' );

		jQuery.ajax({
			method: "POST",
			data: {
				'action': 'cancel-tincanny-module-upload',
				'directory': directory,
				'status': 'abort',
				'fullzip': app.isfullZipUpload( app ),
				'security' : config.nonce,
			},
			url: config.rest_url + config.rest_namespace,
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', config.rest_nonce);
			}
		});
	}

	isfullZipUpload( app ) {
		const $fullZipUpload = app.getElByID( app.ids.fullZipUpload );
		const checked = $fullZipUpload ? $fullZipUpload.checked : false;
		return checked ? 1 : 0;
	}

	setSettings( app ) {
		let settings = app.getConfig( 'settings' );
		settings = settings || {};
		settings.fullZipUpload = app.isfullZipUpload( app );
		app.setConfigItem( 'settings', settings );
		// Show / Hide Max File Size warning.
		const $fullZipMax = app.getElByID( app.ids.fullZipMax );
		if ( $fullZipMax ) {
			$fullZipMax.style.display = settings.fullZipUpload ? 'block' : 'none';
		}
	}

	getConfig( key ) {
		if (typeof window.top.tincannyZipUploadData === 'undefined') {
			console.log( 'window.top.tincannyZipUploadData is undefined' );
			window.top.tincannyZipUploadData = this.config;
		}

		// check if key is undefined.
		if ( ! key || typeof key === 'undefined' ) {
			return window.top.tincannyZipUploadData;
		}

		if ( typeof key === 'string' ) {
			return window.top.tincannyZipUploadData[key] || '';
		}

		return '';

	}

	setConfigItem( key, data ) {
		if (typeof window.top.tincannyZipUploadData === 'undefined') {
			console.log( 'window.top.tincannyZipUploadData is undefined' );
			window.top.tincannyZipUploadData = this.config;
		}
		window.top.tincannyZipUploadData[key] = data;
	}
}


jQuery(document).ready(($) => {
	let key = false; // review test VC to understand this setup.
	tincannyMediaUpload = new TincannyMediaUploadClass( $, key );
} );