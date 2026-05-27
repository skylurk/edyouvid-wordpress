/**
 * LoginPress Login Widget Block JSON
 *
 * @since 4.0.0
 */
const { __ } = wp.i18n; // Translation functions
const { registerBlockType } = wp.blocks; // Block registration
const { TextControl, ToggleControl, TextareaControl, PanelBody, RangeControl, ColorPicker } = wp.components; // UI components
const { InspectorControls } = wp.blockEditor; // Sidebar controls
const el = wp.element.createElement; // Helper for creating elements

registerBlockType('loginpress/login-widget', {
    title: __('LoginPress: Login Widget', 'loginpress-pro'),
    icon: 'lock',
    category: 'widgets',
    attributes: {   // Define block attributes with default values
        loggedInTitle: {
            type: 'string',
            default: __('Welcome %username%', 'loginpress-pro'),
        },
        loggedOutTitle: {
            type: 'string',
            default: __('Login', 'loginpress-pro'),
        },
        loggedOutLinks: {
            type: 'string',
            default: '',
        },
        showLostPasswordLink: {
            type: 'boolean',
            default: true,
        },
        lostPasswordText: {
            type: 'string',
            default: __('Lost your password?', 'loginpress-pro'),
        },
        showRegisterLink: {
            type: 'boolean',
            default: true,
        },
        registrationText: {
            type: 'string',
            default: __('Register', 'loginpress-pro'),
        },
        showRememberMe: {
            type: 'boolean',
            default: true,
        },
        loginRedirectUrl: {
            type: 'string',
            default: '',
        },
        loggedInLinks: {
            type: 'string',
            default: __("Dashboard | %admin_url%\nProfile | %admin_url%/profile.php\nLogout | %logout_url%", 'loginpress-pro'),
        },
        showAvatar: {
            type: 'boolean',
            default: true,
        },
        avatarSize: {
            type: 'number',
            default: 38,
        },
        logoutRedirectUrl: {
            type: 'string',
            default: '',
        },
        errorBgColor: {
            type: 'string',
            default: '#fbb1b7',
        },
        errorTextColor: {
            type: 'string',
            default: '#ae121e',
        },
    },
    edit: function(props) {     // Block editor UI in the Gutenberg editor
        const { attributes, setAttributes } = props;

        return el('div', { className: 'loginpress-widget' },        // Main container div for the widget
            el(InspectorControls, {},
                el(PanelBody, { title: __('Settings', 'loginpress-pro') },
                    el(TextControl, {
                        label: __('Logged-in Title (%username%)', 'loginpress-pro'),
                        value: attributes.loggedInTitle,
                        onChange: function(loggedInTitle) {
                            setAttributes({ loggedInTitle });
                        }
                    }),
                    el(TextControl, {
                        label: __('Logged-out Title', 'loginpress-pro'),
                        value: attributes.loggedOutTitle,
                        onChange: function(loggedOutTitle) {
                            setAttributes({ loggedOutTitle });
                        }
                    }),
                    el(TextareaControl, {
                        label: __('Logged-out Links (Text | href) e.g., Dashboard | %admin_url%', 'loginpress-pro'),
                        value: attributes.loggedOutLinks,
                        onChange: function(loggedOutLinks) {
                            setAttributes({ loggedOutLinks });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Show Lost Password Link', 'loginpress-pro'),
                        checked: attributes.showLostPasswordLink,
                        onChange: function(showLostPasswordLink) {
                            setAttributes({ showLostPasswordLink });
                        }
                    }),
                    attributes.showLostPasswordLink && el(TextControl, {
                        label: __('Lost Password Text', 'loginpress-pro'),
                        value: attributes.lostPasswordText,
                        onChange: function(lostPasswordText) {
                            setAttributes({ lostPasswordText });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Show Register Link', 'loginpress-pro'),
                        checked: attributes.showRegisterLink,
                        onChange: function(showRegisterLink) {
                            setAttributes({ showRegisterLink });
                        }
                    }),
                    attributes.showRegisterLink && el(TextControl, {
                        label: __('Register Text', 'loginpress-pro'),
                        value: attributes.registrationText,
                        onChange: function(registrationText) {
                            setAttributes({ registrationText });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Show "Remember Me"', 'loginpress-pro'),
                        checked: attributes.showRememberMe,
                        onChange: function(showRememberMe) {
                            setAttributes({ showRememberMe });
                        }
                    }),
                    el(TextControl, {
                        label: __('Login Redirect URL', 'loginpress-pro'),
                        value: attributes.loginRedirectUrl,
                        onChange: function(loginRedirectUrl) {
                            setAttributes({ loginRedirectUrl });
                        }
                    }),
                    el(TextareaControl, {
                        label: __('Logged-in Links (Text | HREF ) e.g., Logout | %logout_url%', 'loginpress-pro'),
                        value: attributes.loggedInLinks,
                        onChange: function(loggedInLinks) {
                            setAttributes({ loggedInLinks });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Show Avatar', 'loginpress-pro'),
                        checked: attributes.showAvatar,
                        onChange: function(showAvatar) {
                            setAttributes({ showAvatar });
                        }
                    }),
                    attributes.showAvatar && el(RangeControl, {
                        label: __('Avatar Size', 'loginpress-pro'),
                        value: attributes.avatarSize,
                        onChange: function(avatarSize) {
                            setAttributes({ avatarSize });
                        },
                        min: 20,
                        max: 100
                    }),
                    el(TextControl, {
                        label: __('Logout Redirect URL', 'loginpress-pro'),
                        value: attributes.logoutRedirectUrl,
                        onChange: function(logoutRedirectUrl) {
                            setAttributes({ logoutRedirectUrl });
                        }
                    }),
                    el('p', {}, __('Error Background Color:', 'loginpress-pro')),
                    el(ColorPicker, {
                        label: __('Error Background Color', 'loginpress-pro'),
                        color: attributes.errorBgColor,
                        onChangeComplete: function(value) {
                            setAttributes({ errorBgColor: value.hex });
                        }
                    }),
                    el('p', {}, __('Error Text Color:', 'loginpress-pro')),
                    el(ColorPicker, {
                        label: __('Error Text Color', 'loginpress-pro'),
                        color: attributes.errorTextColor,
                        onChangeComplete: function(value) {
                            setAttributes({ errorTextColor: value.hex });
                        }
                    })
                )
            ),
            el('div', { className: 'login-widget-preview' },
                el('h2', {}, attributes.loggedInTitle),
                attributes.showAvatar && el('img', {
                    src: 'https://placehold.co/' + attributes.avatarSize + 'x' + attributes.avatarSize + '/000000/ffffff@3x.png?text=Login to see%0AAvatar',
                    alt: 'Avatar'
                }),
                el('p', {}, __('Login form or links will appear here.', 'loginpress-pro'))
            )
        );
    },
    save: function() {
        // Dynamic rendering handled in PHP, no save content needed
        return null;
    }
});
