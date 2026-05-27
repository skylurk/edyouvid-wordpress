(function($) {

	/**
	 * Select2 User AJAX Search w/ Caching
	 */
	class UserSearchULGM {
		constructor( config, $searchField ) {
			this.config = config;
			this.i18n = config.i18n;
			this.$searchField = $searchField;
			this.$searchFieldWrapper = $searchField.closest( config.selectors.wrapper );
			this.cache = {};
			this.lastTerm = '';
			this.lastResults = [];
			this.setLastData = false;
			this.isSearching = false;
			
			this.initSelect2();
			this.bindEvents();
		}

		/**
		 * Initialize Select2.
		 * 
		 * @returns {void}
		 */
		initSelect2() {

			const args = {
				allowClear: false,
				placeholder: this.i18n.placeholder,
				dropdownParent: this.$searchFieldWrapper,
				minimumInputLength: this.config.minimumInputLength,
				escapeMarkup: function( m ) { return m; },
				language: this.select2Language(),
				ajax: {
					url:            this.config.ajax_url,
					dataType:       'json',
					delay:          1000,
					cache:          true,
					data:           this.ajaxData.bind( this ),
					transport:      this.ajaxTransport.bind( this ),
					processResults: this.processAjaxResults.bind( this )
				}
			};

			// Initialize Select2.
			this.$searchField.select2( args );
		}

		/**
		 * Bind events to class.
		 * 
		 * @returns {void}
		 */
		bindEvents() {
			this.$searchField.on('select2:opening', this.onSelect2Opening.bind(this));
			this.$searchField.on('select2:open', this.onSelect2Open.bind(this));
			this.$searchField.on('select2:selecting', this.onSelect2Selecting.bind(this));
			this.$searchField.on('select2:select', this.onUserSelected.bind(this));
		}

		/**
		 * Handle Select2 opening.
		 * 
		 * @param {*} e 
		 */
		onSelect2Opening(e) {
			// Prevent the dropdown from opening
			if ( this.lastTerm && ! $('.select2-container--open').length && !this.isSearching ) {

				e.preventDefault();
				
				// Set the search term to the last term
				this.$searchField.data('select2').dropdown.$search.val( this.lastTerm );
		
				// Trigger the search
				this.isSearching = true;
				this.$searchField.data('select2').dropdown.handleSearch();
				this.isSearching = false;
		
				// Flag to load the last results
				this.setLastData = true;
			}
		}

		/**
		 * Handle Select2 open.
		 * 
		 * @param {*} e
		 * 
		 * @returns {void}
		 */
		onSelect2Open() {
			$('.select2-search__field').on('input', this.handleInputChange.bind(this));
			// Set the focus back to the search field
			$('.select2-search__field').focus();
		}

		/**
		 * Handle input change.
		 * 
		 * @param {*} e 
		 */
		handleInputChange(e) {
			const currentValue = e.target.value;
			if ( currentValue && currentValue !== this.lastTerm ) {
				// Clear the current results
				this.$searchField.data('select2').dataAdapter.query({}, function() {});
			} else if ( ! currentValue ) {
				// The search field has been cleared
				this.lastTerm = '';
				this.setLastData = false;
				this.lastResults = [];
			}
		}

		/**
		 * Handle Select2 selecting.
		 * 
		 * @param {*} e
		 * 
		 * @returns {void}
		 */
		onSelect2Selecting(e) {
			if (this.setLastData) {
				// Reset the flag
				this.setLastData = false;
				// Update the data of the existing Select2 instance
				this.$searchField.data('select2').options.options.data = lastResults;
				// Trigger the 'change' event to update the display
				this.$searchField.trigger('change');
			}
		}

		/**
		 * Handle user selected.
		 * 
		 * @param {*} e
		 * 
		 * @returns {void}
		 */
		onUserSelected(e) {
			const data = e.params.data;
			if ( ! data || ! data.id || ! data.selected) {
				return;
			}
			// Redirect to the user's progress report.
			location.href = this.config.redirect_url + '&user-id=' + data.id;
		}

		/**
		 * Select2 Language functions
		 * 
		 * @returns {object}
		 */
		select2Language() {
			const i18n = this.i18n;
			let language = {};
			language.errorLoading = function() {
				// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
				return i18n.searching;
			};

			language.inputTooLong = function( args ) {
				const overChars = args.input.length - args.maximum;
				if ( 1 === overChars ) {
					return i18n.input_too_long_1;
				}
				return i18n.input_too_long_n.replace( '%qty%', overChars );
			};

			language.inputTooShort = function( args ) {
				const remainingChars = args.minimum - args.input.length;
				if ( 1 === remainingChars ) {
					return i18n.input_too_short_1;
				}
				return i18n.input_too_short_n.replace( '%qty%', remainingChars );
			};

			language.loadingMore = function() {
				return i18n.load_more;
			};

			language.maximumSelected = function( args ) {
				if ( args.maximum === 1 ) {
					return i18n.selection_too_long_1;
				}
				return i18n.selection_too_long_n.replace( '%qty%', args.maximum );
			};

			language.noResults = function() {
				return i18n.no_matches;
			};

			language.searching = function() {
				return i18n.searching;
			};

			return language;
		}

		/**
		 * AJAX data.
		 * 
		 * @param {object} params
		 * 
		 * @returns {object}
		 */
		ajaxData( params ) {
			return {
				group_id: this.config.current_group_id,
				action: 'uo_groups_search_user',
				security: this.config.nonce,
				search: params.term ? params.term : ''
			};
		}

		/**
		 * AJAX transport - handles caching.
		 * 
		 * @param {*} params 
		 * @param {*} success 
		 * @param {*} failure 
		 * @returns 
		 */
		ajaxTransport( params, success, failure ) {

			const app = this;

			// Get the current search term
			const currentTerm = params.data.search;

			// Update lastTerm
			app.lastTerm = currentTerm;

			// If the results for this term are cached, use them
			if ( app.cache[currentTerm] ) {
				success( app.cache[currentTerm] );
				return;
			}
			
			// Otherwise, make the AJAX call
			const $request = $.ajax(params);
			
			$request.then(function(data) {
				// Cache the results
				app.cache[currentTerm] = data;
				success(data);
			});
			
			$request.fail(failure);
			return $request;
		}

		/**
		 * Process Ajax results.
		 * 
		 * @param {object} data 
		 * @returns {object}
		 */
		processAjaxResults( data ) {
			// Handle Auth errors.
			if ( data.error ) {
				this.handleErrors( data.error );
				return { results: [] };
			}

			// Save the last results
			this.lastResults = data;

			// Return the results
			let results = [];
			if ( data ) {
				$.each( data, function( id, option ) {
					results.push({
						id: option.id,
						text: option.text
					});
				});
			}

			return { results: results };
		}

		/**
		 * Handle Ajax error message.
		 * 
		 * @param {*} error
		 * 
		 * @returns {void}
		 */
		handleErrors( error ) {
			// Handle Auth errors.
			if ( error ) {
				if ( 'auth' === error.type ) {
					// Remove Select2.
					this.$searchField.select2( 'destroy' );
					// Replace Select with error message and add class.
					this.$searchFieldWrapper
					.html( '<p>' + error.message + '</p>' )
					.addClass( 'ulgm-user-search-error' );
				}
			}
		}
	}

	/**
	 * Initialize UserSearchULGM
	 * 
	 * @uses jQuery
	 * @uses Select2
	 * @uses window.ULGM_User_Search_Data
	 * @uses UserSearchULGM
	 */
	$(function() {
		if ( window.ULGM_User_Search_Data && window.ULGM_User_Search_Data.selectors.searchField ) {
			const $searchField = $( window.ULGM_User_Search_Data.selectors.searchField ); 
			if ( $searchField.length ) {
				new UserSearchULGM(window.ULGM_User_Search_Data, $searchField);
			}
		}
	});

})(jQuery);