( function( blocks, editor, i18n, element, components ) {
	const el = element.createElement;
	const useBlockProps = editor.useBlockProps;
	const InspectorControls = editor.InspectorControls;
	const CheckboxControl = components.CheckboxControl;
	const Fragment = element.Fragment;

	blocks.registerBlockType( 'loginpress/social-login', {
		title: 'LoginPress Social Login',
		icon: 'groups',
		category: 'widgets',
		attributes: {
			disableApple: { type: 'boolean', default: false },
			disableGoogle: { type: 'boolean', default: false },
			disableFacebook: { type: 'boolean', default: false },
			disableTwitter: { type: 'boolean', default: false },
			disableLinkedin: { type: 'boolean', default: false },
			disableMicrosoft: { type: 'boolean', default: false },
			disableGithub: { type: 'boolean', default: false },
			disableDiscord: { type: 'boolean', default: false },
			disableWordpress: { type: 'boolean', default: false },
			disableAmazon: { type: 'boolean', default: false },
			disableTwitch: { type: 'boolean', default: false },
			disablePinterest: { type: 'boolean', default: false },
			disableSpotify: { type: 'boolean', default: false },
			disableReddit: { type: 'boolean', default: false },
			disableDisqus: { type: 'boolean', default: false }
		},
		edit: function( props ) {
			const blockProps = useBlockProps();
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			return el(
				Fragment,
				null,
				el( InspectorControls, {},
					el( 'div', { className: 'loginpress-sl-controls' },
						Object.keys(attributes).map((key) => {
							const provider = key.replace('disable', '').toLowerCase();
							const providerName = provider.charAt(0).toUpperCase() + provider.slice(1);
							const status = LoginPressSocialLogins.statuses?.[provider] || 'not verified';
							const isVerified = status === 'verified';
							const isDisabled = !isVerified;
						
							return el(CheckboxControl, {
								key: key,
								label: `Enable ${providerName} (${status})`,
								checked: isDisabled ? false : !attributes[key], // Show unchecked if disabled, otherwise show enabled state
								disabled: isDisabled,
								onChange: (value) => {
									if (!isDisabled) {
										setAttributes({ [key]: !value }); // Store as "disable[Provider]", so invert the value
									}
								}
							});
						})
						
						
				 )
				),
				el( 'div', blockProps, 'LoginPress Social Login' )
			);
		},
		save: function() {
			return null; // Server-side render
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.i18n,
	window.wp.element,
	window.wp.components
);
