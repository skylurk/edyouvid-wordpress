import React, { useState, useEffect } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import SettingField from './SettingField';
import './SocialLoginSettings.css';
import InfoBox from './InfoBox';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, rectSortingStrategy } from '@dnd-kit/sortable';
import { restrictToParentElement } from '@dnd-kit/modifiers';
import { SortableItem } from './SortableItem'; // We'll create this component next

const SocialLoginSettings = () => {
  const activeAddons = window.loginpressProData?.activeAddons || [];

  if (!activeAddons.includes('social-login')) {
    return;
  }
  const [activeTab, setActiveTab] = useState('settings');
  const [settings, setSettings] = useState({});
  const [providers, setProviders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedProvider, setSelectedProvider] = useState(null);
  const [providersOrder, setProvidersOrder] = useState([]);
  const [showSaveButton, setShowSaveButton] = useState(true);
  const [sortedProviders, setSortedProviders] = useState([]);
  const [providerNotes, setProviderNotes] = useState({});
  const [saveChanges, setSaveChanges] = useState({text: '', type: ''});
  const [initialSettings, setInitialSettings] = useState({});
  const [appleReconfigure, setAppleReconfigure] = useState(false);
  const loginpressPath = '/wp-content/plugins/loginpress-pro/addons/social-login';
const [copied, setCopied] = useState(false);
const handleCopyClick = (e) => {
	const button = e.target.closest('.loginpress-copy-btn') || e.target.closest('.loginpress-copy-link');
	const textToCopy = button?.dataset?.copy;
  
	if (!button || !textToCopy) return;
  
	// Use Clipboard API if available
	if (navigator.clipboard && navigator.clipboard.writeText) {
	  navigator.clipboard.writeText(textToCopy).then(() => {
		showTooltip(button, 'Copied');
	  }).catch((err) => {
		fallbackCopy(textToCopy, button);
	  });
	} else {
	  // Fallback for unsupported browsers
	  fallbackCopy(textToCopy, button);
	}
  };
  
  const fallbackCopy = (text, button) => {
	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.setAttribute('readonly', '');
	textarea.style.position = 'absolute';
	textarea.style.left = '-9999px';
	document.body.appendChild(textarea);
  
	const selected = document.getSelection().rangeCount > 0 ? document.getSelection().getRangeAt(0) : false;
  
	textarea.select();
	try {
	  const success = document.execCommand('copy');
	  showTooltip(button, success ? 'Copied' : 'Failed to copy');
	} catch (err) {
	  showTooltip(button, 'Failed to copy');
	}
  
	document.body.removeChild(textarea);
  
	// Restore previous selection
	if (selected) {
	  const selection = document.getSelection();
	  selection.removeAllRanges();
	  selection.addRange(selected);
	}
  };
  
  const showTooltip = (button, message) => {
	button.setAttribute('data-tooltip', message);
	setTimeout(() => {
	  button.setAttribute('data-tooltip', 'Copy');
	}, 2000);
  };
  
  

    const handleCopyClickShort = () => {
      if (navigator.clipboard) {
        navigator.clipboard.writeText('[loginpress_social_login]').then(() => {
          setCopied(true);
          setTimeout(() => setCopied(false), 2000);
        }).catch(err => {
        });
      } else {
        // Fallback for browsers that don't support clipboard API
        const textArea = document.createElement('textarea');
        textArea.value = '[loginpress_social_login]';
        document.body.appendChild(textArea);
        textArea.select();
        try {
          document.execCommand('copy');
          setCopied(true);
          setTimeout(() => setCopied(false), 2000);
        } catch (err) {
        }
        document.body.removeChild(textArea);
      }
    };
  useEffect(() => {
   
  
    // Attach listener
    document.addEventListener('click', handleCopyClick);
  
    // Cleanup
    return () => {
      document.removeEventListener('click', handleCopyClick);
    };
  }, []);
  

  const handleProviderSettingChange = (providerClass, fieldName, value) => {
    handleSettingChange(fieldName, value);
    
    // Mark provider as having changes
    if ( providerClass !== fieldName ){
      setProviderNotes(prev => ({
          ...prev,
          [providerClass]: true
      }));
    }
  };

  // Initialize and sort providers based on provider_order
  useEffect(() => {
    if (providers.length && providersOrder.length) {
      const sorted = [...providers].sort((a, b) => {
        const indexA = providersOrder.indexOf(a.class);
        const indexB = providersOrder.indexOf(b.class);
        return indexA - indexB;
      });
      setSortedProviders(sorted);
    }
  }, [providers, providersOrder]);

  // Handle drag and drop reordering
  // Initialize sensors for drag and drop
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  // Updated handleDragEnd function
  const handleDragEnd = (event) => {
    const { active, over } = event;
    
    if (active.id !== over.id) {
      setSortedProviders((items) => {
        const oldIndex = items.findIndex(item => item.class === active.id);
        const newIndex = items.findIndex(item => item.class === over.id);
        const newItems = arrayMove(items, oldIndex, newIndex);

        // Extract just the provider classes in new order
        const newOrder = newItems.map(item => item.class);
        
        // Update the provider_order in settings
        handleSettingChange('provider_order', newOrder);

        // Send update to API
        jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'loginpress_save_social_login_order',
            loginpress_provider_order: newOrder,
            security: loginpress_social_ajax.nonce
          },
          success: function(response) {
            // Optional: Handle success
          },
          error: function(xhr, status, error) {
          }
        });

        return newItems;
      });
    }
  };

  // Fetch initial data
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Fetch settings from API
        const settingsResponse = await apiFetch({ path: '/loginpress/v1/social-login-settings' });
        
        // Initialize providers data
        const providersData = window.loginpressProviders || [];
        const extraProviders = window.loginpressProvidersExtra || [];
        const allProviders = [...providersData, ...extraProviders];
        
        // Get provider order from settings or use default
        let order = settingsResponse.provider_order || 
                   '["facebook", "twitter", "gplus", "linkedin", "microsoft", "apple", "discord", "wordpress", "github", "amazon", "pinterest", "disqus", "reddit", "spotify", "twitch"]';
        try {
          order = typeof order === 'string' ? JSON.parse(order) : order;
        } catch (e) {
          order = [];
        }
        // Filter and sort providers based on order
        const sortedProviders = order
          .map(key => allProviders.find(p => p.class === key))
          .filter(Boolean)
          .concat(allProviders.filter(p => !order.includes(p.class)));

        setSettings(settingsResponse);
        setInitialSettings({...settingsResponse});
        setProviders(sortedProviders);
        setProvidersOrder(order);
        setLoading(false);
        
        // Check URL for provider parameter
        const urlParams = new URLSearchParams(window.location.search);
        const providerParam = urlParams.get('provider');
        if (providerParam) {
          const provider = allProviders.find(p => p.class === providerParam);
          if (provider) {
            setTimeout(() => {
              setSelectedProvider(provider);
              setActiveTab('providers');
            }, 100);
          }
        }
      } catch (error) {
        setLoading(false);
      }
    };
    
    fetchData();
  }, []);

  // Handle setting changes
  const handleSettingChange = (name, value) => {
    if(name === 'restrict_admin_sl'){
      if (value === true){
        value = 'on';
      } else if (value === false){
        value = 'off';
      }
    }
    setSettings(prev => {
        const newSettings = {
          ...prev,
          [name]: value
        };
        return newSettings;
    });
  };

  // Handle multicheck changes
  const handleMultiCheckChange = (name, key, checked) => {
    setSettings(prev => ({
      ...prev,
      [name]: {
        ...prev[name],
        [key]: checked === true ? key : ''
      }
    }));
  };

  // Save settings
  const saveSettings = async () => {
    setProviderNotes(prev => {
      const newNotes = {...prev};
      Object.keys(prev).forEach(providerClass => {
        if (prev[providerClass]) {
          newNotes[providerClass] = false;
        }
      });
      return newNotes;
    });
  
    let newSettings = { ...settings };
  
    // Check which providers have changed settings
    providers.forEach(provider => {
      const providerClass = provider.class;
      const providerKeys = Object.keys(newSettings).filter(key => 
        key.startsWith(providerClass) && key !== `${providerClass}_status`
      );
  
      // Check if any provider-specific setting has changed
      const hasChanged = providerKeys.some(key => 
        JSON.stringify(newSettings[key]) !== JSON.stringify(initialSettings[key])
      );
  
      if (hasChanged) {
        settings[`${providerClass}_status`] = 'not verified';
        if (providerClass == 'apple'){
          settings[`${providerClass}_secret`] = '';
        }
        newSettings[`${providerClass}_status`] = 'not verified';
      }
    });
  
    try {
      await apiFetch({
        path: '/loginpress/v1/social-login-settings-update',
        method: 'POST',
        data: newSettings
      });
      setSaveChanges({text: __('Settings saved successfully!', 'loginpress-pro'), type: 'success'});
      // Update initial settings to current after successful save
      setInitialSettings({...newSettings});
    } catch (error) {
      setSaveChanges({text: __('Settings saved failed!', 'loginpress-pro'), type: 'error'});
    } finally {
      setTimeout(function () {
        setSaveChanges({text: '', type: ''});
      }, 3000);
    }
  };

  // Handle provider status
  const getProviderStatus = (provider) => {
    const status = settings[`${provider.class}_status`];
    const isEnabled = settings[provider.class] === 'on';
    
    if (isEnabled && status === 'verified') {
      return { class: 'verified', text: __('Enabled', 'loginpress-pro') };
    } else if (isEnabled && status !== 'verified') {
      return { class: 'not-verified-button', text: __('Not Verified', 'loginpress-pro') };
    } else if (!isEnabled) {
      return { class: 'off', text: __('Disabled', 'loginpress-pro') };
    } else if (isEnabled && status === 'yet-to-verify') {
      return { class: 'yet-to-verify-button', text: __('Yet to Verify', 'loginpress-pro') };
    }
    return { class: 'off', text: __('Disabled', 'loginpress-pro') };
  };

  // Update URL when selecting provider
  const handleProviderSelect = (provider) => {
    setSelectedProvider(provider);
    setShowSaveButton(true);
    setAppleReconfigure(false);
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('provider', provider.class);
    window.history.pushState({}, '', url);
  };

  // Handle back to providers list
  const handleBackToProviders = () => {
    setSelectedProvider(null);
    setShowSaveButton(false);
    setAppleReconfigure(false);
    // Remove provider from URL
    const url = new URL(window.location);
    url.searchParams.delete('provider');
    window.history.replaceState({}, '', url);
  };

  // Render Settings Tab
  const renderSettingsTab = () => (
    <div className="loginpress-settings-tab">
      <div className="loginpress-form-field">
        <label>{__('Enable Social Login on', 'loginpress-pro')}</label>
        <div className='loginpress-field-group'>
			<div className="loginpress-checkbox-group">
          {['login', 'register', 'comment'].map(key => (
            <label key={key}>
              
			  <span className='loginpress-checkbox'><input
                type="checkbox"
                checked={settings.enable_social_login_links?.[key] || false}
                onChange={(e) => handleMultiCheckChange(
                  'enable_social_login_links', 
                  key, 
                  e.target.checked
                )}
              /></span>
              {key === 'login' ? __('Login Form', 'loginpress-pro') : key === 'register' ? __('Register Form', 'loginpress-pro') : __('Comment Form', 'loginpress-pro')}
            </label>
          ))}
        </div>
        <p className="loginpress-description">
          {__('Enable Social Login on WordPress default Login, Register, and Comment forms.', 'loginpress-pro')}
        </p>
		</div>
      </div>

      <SettingField
        name="restrict_admin_sl"
        label={__('Disallow Admins', 'loginpress-pro')}
        type="checkbox"
        value={settings.restrict_admin_sl === 'on' ? true : false || false}
        onChange={handleSettingChange}
        desc={__('Restrict Admins from logging in using Social Login.', 'loginpress-pro')}
      />

      <SettingField
        name="social_login_button_label"
        label={__('Social Login Button Label', 'loginpress-pro')}
        type="text"
        value={settings.social_login_button_label || ''}
        onChange={handleSettingChange}
        placeholder={__('Login with %provider%', 'loginpress-pro')}
        desc3={__('Customize the label for social login buttons. Use <code>%provider%</code> to dynamically include the social provider name in the label. For example, "Login with %provider%" or "Continue with %provider%".', 'loginpress-pro')}
      />

      <div className="loginpress-form-field">
        <label>{__('Social Login Shortcode', 'loginpress-pro')}</label>
        <div>
			<div className="loginpress-shortcode-field">
          <input
            type="text"
            value="[loginpress_social_login]"
            readOnly
            className="regular-text"
          />
          <button
      className="button loginpress-copy-btn"
      onClick={handleCopyClickShort}
      aria-label={copied ? __('Copied', 'loginpress-pro') : __('Copy', 'loginpress-pro')}
    >{!copied ? (
		<svg className="autologin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
		  <g clipPath="url(#clip0_27_294)">
			<path
			  d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z"
			  fill="#869AC1"
			/>
			<path
			  d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z"
			  fill="#869AC1"
			/>
		  </g>
		  <defs>
			<clipPath id="clip0_27_294">
			  <rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)" />
			</clipPath>
		  </defs>
		</svg>
	  ) : (
		<svg width="18px" height="13.5px" viewBox="0 0 18 13.5" xmlns="http://www.w3.org/2000/svg">
		  <path
			className="st0"
			fill="#1F693F"
			d="M15.6,0L7,8.6L2.4,4L0,6.5l7,7l11-11L15.6,0z"
		  />
		</svg>
	  )}</button>
        </div>
        <p className="loginpress-description">
          {__('Place this shortcode where you want to add social login buttons.', 'loginpress-pro')}
        </p>
		</div>
      </div>
    </div>
  );

  // Render Styles Tab with data from API
  const renderStylesTab = () => {
    const buttonStyles = [
      { value: 'default', label: __('Default', 'loginpress-pro'), icon: 'default-social-login.svg' },
      { value: 'full_width', label: __('Full Width', 'loginpress-pro'), icon: 'social-full-width.svg' },
      { value: 'icons', label: __('Square Icons', 'loginpress-pro'), icon: 'social-icons.svg' },
      { value: 'round_icons', label: __('Round Icons', 'loginpress-pro'), icon: 'round-icon.svg' }
    ];

    const buttonPositions = [
      { value: 'default', label: __('Default', 'loginpress-pro'), icon: 'below-with-seprator.svg' },
      { value: 'below', label: __('Below', 'loginpress-pro'), icon: 'default.svg' },
      { value: 'above', label: __('Above', 'loginpress-pro'), icon: 'above.svg' },
      { value: 'above_separator', label: __('Above with Separator', 'loginpress-pro'), icon: 'above-with-separtor.svg' }
    ];

    return (
      <div className="loginpress-styles-tab">
        <SettingField
          name="social_button_styles"
          label={__('Button Styles', 'loginpress-pro')}
          type="radio-image"
          value={settings.social_button_styles || 'default'}
          onChange={handleSettingChange}
          options={buttonStyles.map(style => ({
            value: style.value,
            label: (
              <>                <span>{style.label}</span>

				<div className='loginpress-icon'>
                <img 
                  src={`${loginPressGlobal.socialDirPath ? loginPressGlobal.socialDirPath: loginpressPath}/assets/img/${style.icon}`} 
                  alt={style.label} 
                />
              </div>
			  </>
            )
          }))}
        />

        <SettingField
          name="social_button_position"
          label={__('Button Position', 'loginpress-pro')}
          type="radio-image"
          value={settings.social_button_position || 'default'}
          onChange={handleSettingChange}
          options={buttonPositions.map(position => ({
            value: position.value,
            label: (
              <>
			  <span>{position.label}</span>
				<div className='loginpress-icon'>
                <img 
                  src={`${loginPressGlobal.socialDirPath ? loginPressGlobal.socialDirPath: loginpressPath}/assets/img/${position.icon}`} 
                  alt={position.label} 
                />
				</div>
                
              </>
            )
          }))}
        />
      </div>
    );
  };

  // Render Provider Settings
// Then modify your renderProviderSettings to receive these props:
const renderProviderSettings = ({
  provider,
  settings,
  providerNotes,
  handleSettingChange,
  getProviderStatus,
  loginpressReactData,
  window,
}) => {
  const status = getProviderStatus(provider);
  const isEnabled = settings[provider.class] === "on";
  const logoName = provider.class === "gplus" ? "google" : provider.class;
  const showNote = providerNotes[provider.class] || false;

  const isVerifyButtonDisabled = () => {
    const providerConfig = {
      facebook: {
        required: ['facebook_app_id', 'facebook_app_secret', 'facebook_redirect_uri']
      },
      twitter: {
        required: ['twitter_oauth_token', 'twitter_token_secret', 'twitter_callback_url']
      },
      gplus: {
        required: ['gplus_client_id', 'gplus_client_secret', 'gplus_redirect_uri']
      },
      linkedin: {
        required: ['linkedin_client_id', 'linkedin_client_secret', 'linkedin_redirect_uri']
      },
      microsoft: {
        required: ['microsoft_app_id', 'microsoft_app_secret', 'microsoft_redirect_uri']
      },
      github: {
        required: ['github_client_id', 'github_client_secret', 'github_redirect_uri']
      },
      discord: {
        required: ['discord_client_id', 'discord_client_secret', 'discord_redirect_uri']
      },
      wordpress: {
        required: ['wordpress_client_id', 'wordpress_client_secret', 'wordpress_redirect_uri']
      },
      apple: {
        required: ['apple_service_id', 'apple_key_id', 'apple_team_id', 'apple_p_key']
      },
      disqus: {
        required: ['disqus_client_id', 'disqus_client_secret', 'disqus_callback_url']
      },
      amazon: {
        required: ['amazon_client_id', 'amazon_client_secret', 'amazon_redirect_uri']
      },
      pinterest: {
        required: ['pinterest_client_id', 'pinterest_client_secret', 'pinterest_redirect_uri']
      },
      reddit: {
        required: ['reddit_client_id', 'reddit_client_secret', 'reddit_redirect_uri']
      },
      spotify: {
        required: ['spotify_client_id', 'spotify_client_secret', 'spotify_redirect_uri']
      },
      twitch: {
        required: ['twitch_client_id', 'twitch_client_secret', 'twitch_redirect_uri']
      }
    };
    // Get the required fields for this provider
    const requiredFields = providerConfig[provider.class]?.required || [];
    
    // Check if any required field is empty
    const hasEmptyRequiredField = requiredFields.some(field => {
      const value = settings[field];
      return !value || value.trim() === '';
    });
    return hasEmptyRequiredField;
  }

  // Verification button click handler
  const handleVerifyClick = () => {
    const providerClass = provider.class;
    // Get the redirect URI from settings for this provider
    let redirectUriKey = `${providerClass}_redirect_uri`;
    if (providerClass === "twitter") {
      redirectUriKey = `twitter_callback_url`;
    } else if (providerClass === "disqus") {
      redirectUriKey = `disqus_callback_url`;
    }
    let baseRedirectUrl = settings[redirectUriKey] || '';
    if (providerClass === 'apple'){
      baseRedirectUrl = loginpressReactData.wp_login_url;

      // Remove the wp-login.php part from the URL
      baseRedirectUrl = baseRedirectUrl.replace('wp-login.php', '');
      
      // Then append your query string
      baseRedirectUrl += '?lpsl_login_id=apple_login';
    } 
    
    // ✅ Ensure lpsl_login_id is present if missing
    if (!baseRedirectUrl.includes('lpsl_login_id=')) {
      // Decide what default value to add if none is set
      baseRedirectUrl += (baseRedirectUrl.includes('?') ? '&' : '?') + 'lpsl_login_id=' + providerClass + '_login';
    }

    // Append verification parameter
    const redirectUrl = baseRedirectUrl.includes("?")
      ? `${baseRedirectUrl}&verification=1`
      : `${baseRedirectUrl}?verification=1`;

    // Open a popup tab
    const newTab = window.open(redirectUrl, "_blank", "width=600,height=600");

    window.addEventListener("message", function (event) {
      if (event.origin !== loginpress_pro_local.homeUrl) {
        return;
      }

      const data = event.data;
      if (data === "verified") {
        // Send AJAX request to update provider verification
        jQuery.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "loginpress_update_verification",
            provider: providerClass,
            security: loginpress_social_ajax.nonce,
          },
		success: function (response) {
            if (response.success) {
              location.reload(); // Reload the page on success
            }
          },
		error: function () {},
        });
      }
    });
  };
  const baseSiteUrl = loginpressReactData.wp_login_url.replace(
    /\/wp-login\.php$/,
    ""
  );
  // Define provider-specific settings
  const providerSettings = {
    facebook: [
      {
        name: "facebook_app_id",
        label: __("Facebook App ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter your Facebook App ID.", "loginpress-pro"),
      },
      {
        name: "facebook_app_secret",
        label: __("Facebook App Secret Key", "loginpress-pro"),
        type: "text",
        desc: __("Enter your Facebook App Secret Key.", "loginpress-pro"),
      },
      {
        name: "facebook_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=facebook_check`,
        desc: sprintf(
          __(
            '<span id="facebook_redirect_uri"><a href="%s?lpsl_login_id=facebook_check">%s?lpsl_login_id=facebook_check</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%s?lpsl_login_id=facebook_check" data-target="facebook_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span><span class="lp-callback-url"> %s <span class="loginpress-copy-link">',
            "loginpress-pro"
          ),
          `${loginpressReactData.wp_login_url}`,
          `${loginpressReactData.wp_login_url}`,
          `${loginpressReactData.wp_login_url}`,
          __(
            'Click to copy and paste the callback URL under "Valid OAuth Redirect URIs" in your Facebook API settings.',
            "loginpress-pro"
          )
        ),
      },
    ],
    twitter: [
      {
        name: "twitter_api_version",
        label: __("Twitter API Version", "loginpress-pro"),
        type: "select",
        options: [
          { value: "oauth1", label: __("OAuth 1.1", "loginpress-pro") },
          { value: "oauth2", label: __("OAuth 2.0", "loginpress-pro") },
        ],
        desc: __("Select the Twitter API version to use.", "loginpress-pro"),
      },
      {
        name: "twitter_oauth_token",
        label: __("Twitter API key", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Consumer API key.", "loginpress-pro"),
      },
      {
        name: "twitter_token_secret",
        label: __("Twitter API secret key", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Consumer API secret key.", "loginpress-pro"),
      },
      {
        name: "twitter_callback_url",
        label: __("Twitter Callback URL", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=twitter_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitter_callback_url"><a href=%2$s?lpsl_login_id=twitter_login>%2$s?lpsl_login_id=twitter_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=twitter_login" data-target="twitter_callback_url"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter Your Callback URL:", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    gplus: [
      {
        name: "gplus_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "gplus_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "gplus_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=gplus_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url"> %1$s <span class="loginpress-copy-link"><span id="gplus_redirect_uri"><a href="%2$s?lpsl_login_id=gplus_login">%2$s?lpsl_login_id=gplus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=gplus_login" data-target="gplus_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    linkedin: [
      {
        name: "linkedin_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "linkedin_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "linkedin_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=linkedin_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="linkedin_redirect_uri"><a href="%2$s?lpsl_login_id=linkedin_login">%2$s?lpsl_login_id=linkedin_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=linkedin_login" data-target="linkedin_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    microsoft: [
      {
        name: "microsoft_app_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "microsoft_app_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "microsoft_tenant_type",
        label: __("Tenant Type", "loginpress-pro"),
        type: "select",
        options: [
          {
            label: __("Single Tenant", "loginpress-pro"),
            value: "tenant_id",
          },
          {
            label: __("Multitenant", "loginpress-pro"),
            value: "organizations",
          },
          {
            label: __(
              "Multitenant and personal Microsoft accounts",
              "loginpress-pro"
            ),
            value: "common",
          },
          {
            label: __("Personal Microsoft accounts only", "loginpress-pro"),
            value: "consumers",
          },
        ],
        desc2: __("Select the Microsoft Tenant type.", "loginpress-pro"),
      },
      {
        name: "microsoft_tenant_id",
        label: __("Tenant ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Microsoft Entra Tenant ID.", "loginpress-pro"),
      },
      {
        name: "microsoft_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: loginpressReactData.wp_login_url,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="microsoft_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s" data-target="microsoft_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    github: [
      {
        name: "github_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "github_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "github_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=github_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="github_redirect_uri"><a href=%2$s?lpsl_login_id=github_login>%2$s?lpsl_login_id=github_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=github_login" data-target="github_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter Your Callback URL:", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
      {
        name: "github_app_name",
        label: __("Github App Name", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Github App Name.", "loginpress-pro"),
      },
    ],
    discord: [
      {
        name: "discord_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "discord_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "discord_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=discord_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="discord_redirect_uri"><a href=%2$s?lpsl_login_id=discord_login>%2$s?lpsl_login_id=discord_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=discord_login" data-target="discord_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
      {
        name: "discord_generated_url",
        label: __("Discord Generated URL", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Discord Generated URL", "loginpress-pro"),
      },
    ],
    wordpress: [
      {
        name: "wordpress_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "wordpress_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "wordpress_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=wordpress_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="wordpress_redirect_uri"><a href=%2$s?lpsl_login_id=wordpress_login>%2$s?lpsl_login_id=wordpress_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=wordpress_login" data-target="wordpress_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    apple: [
      {
        name: "apple_service_id",
        label: __("Service ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Service ID.", "loginpress-pro"),
      },
      {
        name: "apple_key_id",
        label: __("Key ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Key ID.", "loginpress-pro"),
      },
      {
        name: "apple_team_id",
        label: __("Team ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Team ID", "loginpress-pro"),
      },
      {
        name: "apple_p_key",
        label: __("Private Key", "loginpress-pro"),
        type: "textarea",
        desc: __(
          "Enter The Private Key From the Downloaded File",
          "loginpress-pro"
        ),
      },
      {
        name: "apple_secret",
        label: __("Apple Secret", "loginpress-pro"),
        type: "text",
        readOnly: true,
        desc: __("This JWT token is generated.", "loginpress-pro"),
      },
    ],
    disqus: [
      {
        name: "disqus_client_id",
        label: __("API Key", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your API Key.", "loginpress-pro"),
      },
      {
        name: "disqus_client_secret",
        label: __("API Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your API Secret.", "loginpress-pro"),
      },
      {
        name: "disqus_callback_url",
        label: __("Callback URL", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=disqus_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="disqus_callback_url"><a href=%2$s?lpsl_login_id=disqus_login>%2$s?lpsl_login_id=disqus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=disqus_login" data-target="disqus_callback_url"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Callback URL", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    amazon: [
      {
        name: "amazon_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "amazon_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "amazon_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=amazon_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="amazon_redirect_uri"><a href=%2$s?lpsl_login_id=amazon_login>%2$s?lpsl_login_id=amazon_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=amazon_login" data-target="amazon_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    pinterest: [
      {
        name: "pinterest_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "pinterest_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "pinterest_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="pinterest_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s" data-target="pinterest_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          baseSiteUrl
        ),
      },
    ],
    reddit: [
      {
        name: "reddit_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "reddit_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "reddit_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=reddit_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="reddit_redirect_uri"><a href=%2$s?lpsl_login_id=reddit_login>%2$s?lpsl_login_id=reddit_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=reddit_login" data-target="reddit_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    spotify: [
      {
        name: "spotify_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "spotify_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "spotify_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=spotify_login`,
        desc: sprintf(
          __(
            '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="spotify_redirect_uri"><a href=%2$s?lpsl_login_id=spotify_login>%2$s?lpsl_login_id=spotify_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=spotify_login" data-target="spotify_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
            "loginpress-pro"
          ),
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
    twitch: [
      {
        name: "twitch_client_id",
        label: __("Client ID", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client ID.", "loginpress-pro"),
      },
      {
        name: "twitch_client_secret",
        label: __("Client Secret", "loginpress-pro"),
        type: "text",
        desc: __("Enter Your Client Secret.", "loginpress-pro"),
      },
      {
        name: "twitch_redirect_uri",
        label: __("Redirect URI", "loginpress-pro"),
        type: "text",
        value: `${loginpressReactData.wp_login_url}?lpsl_login_id=twitch_login`,
        desc: sprintf(
          '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitch_redirect_uri"><a href=%2$s?lpsl_login_id=twitch_login>%2$s?lpsl_login_id=twitch_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-copy="%2$s?lpsl_login_id=twitch_login" data-target="twitch_redirect_uri"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
          __("Enter your Redirect URI.", "loginpress-pro"),
          `${loginpressReactData.wp_login_url}`
        ),
      },
    ],
  };

  // Verification status messages
  const verificationMessages = {
    verified: {
      title: __("App Configuration Successful", "loginpress-pro"),
      message: __(
        "You have successfully configured your app. This social provider is now set up correctly, allowing users to log in without issues.",
        "loginpress-pro"
      ),
    },
    notVerified: {
      title: __("Test Your App Configuration", "loginpress-pro"),
      message: __(
        "Before you can start letting your users register with your app, it needs to be tested. This test ensures no users have trouble with the login and registration process. If you encounter an error during the test, review your app settings. Otherwise, your configuration is fine.",
        "loginpress-pro"
      ),
    },
  };
  return (
    <div className="loginpress-provider-settings">
      <div className="loginpress-provider-header">
        <div className="provider-logo-top">
          <img
            src={`${
              loginPressGlobal.socialDirPath
                ? loginPressGlobal.socialDirPath
                : loginpressPath
            }/assets/img/${logoName}.svg`}
            alt={provider.name}
          />
        </div>
        {/* <h2>{provider.name}</h2> */}
      </div>

      {/* Verification Status Box - Only show when provider is enabled */}
      {isEnabled && (
        <div
          className={`provider-container ${
            status.class == "off"
              ? "not-verified"
              : status.class.replace("-button", "")
          }`}
        >
          <div className="provider-description">
            <h3>
              {status.class === "verified"
                ? verificationMessages.verified.title
                : verificationMessages.notVerified.title}
            </h3>
            <p>
              {status.class === "verified"
                ? verificationMessages.verified.message
                : verificationMessages.notVerified.message}
            </p>

            {/* Dynamic note when settings are changed */}
            {showNote && (
              <div className="note-message">
                <p>
                  <strong>{__("Note:", "loginpress-pro")}</strong>{" "}
                  {__(
                    "Save changes to verify the settings.",
                    "loginpress-pro"
                  )}
                </p>
              </div>
            )}
          </div>

          <button
            type="button"
            id="verify-settings"
            className={`button button-primary loginpress-verify-provider ${provider.class}`}
            data-provider={`${provider.class}_status`}
            disabled={status.class === 'enabled' || showNote || isVerifyButtonDisabled()}
            onClick={handleVerifyClick}
          >
            {status.class === "verified"
              ? __("Verify Settings Again", "loginpress-pro")
              : __("Verify Settings", "loginpress-pro")}
          </button>

          <input
            type="hidden"
            className="regular-text"
            id={`loginpress_social_logins[${provider.class}_status]`}
            name={`loginpress_social_logins[${provider.class}_status]`}
            value={status.class}
          />
        </div>
      )}

      <div className="loginpress-setting-field">
        <label className="loginpress-setting-label">{provider.name}</label>
        <div className="loginpress-setting-input">
          <span className="loginpress-checkbox">
            <input
              name={provider.class}
              type="checkbox"
              id={`wpb-loginpress_social_logins[${provider.class}]`}
              checked={isEnabled}
              onChange={(e) =>
                handleProviderSettingChange(
                  provider.class,
                  provider.class,
                  e.target.checked ? "on" : "off"
                )
              }
            />
          </span>
          <label className="loginpress-label-des">
            {__("Enable", "loginpress-pro")} {provider.name}
          </label>
        </div>
      </div>

      {isEnabled && (
        <>
          {/* Button Label */}
          {/* Button Label */}
          {!(provider.class === "apple" &&
            settings["apple_status"] === "verified" &&
            !!settings["apple_secret"]) && (
            <SettingField
              name={`${provider.class}_button_label`}
              label={
                provider.name.replace(/\sLogin$/, "") +
                " " +
                __("Button Label", "loginpress-pro")
              }
              type="text"
              value={
                settings[`${provider.class}_button_label`] !== undefined
                  ? settings[`${provider.class}_button_label`]
                  : ``
              }
              onChange={(name, value) =>
                handleProviderSettingChange(provider.class, name, value)
              }
              desc2={__(
                "Customize the label for the login button",
                "loginpress-pro"
              )}
            />
          )}

          {/* Provider-specific settings */}
          {(function () {
            const isApple = provider.class === "apple";
            const providerFields = providerSettings[provider.class] || [];
            let fieldsToRender = providerFields;
            if (isApple) {
              const appleStatus = settings["apple_status"];
              const appleSecret = settings["apple_secret"];
              const verifiedWithSecret =
                appleStatus === "verified" && !!appleSecret;
              if (!verifiedWithSecret) {
                fieldsToRender = providerFields.filter(
                  (f) => f.name !== "apple_secret"
                );
              } else if (verifiedWithSecret && !appleReconfigure) {
                fieldsToRender = providerFields
                  .filter(
                    (f) =>
                      f.name === "apple_service_id" ||
                      f.name === "apple_secret"
                  )
                  .map((f) => ({ ...f, readOnly: true, disabled: true }));
              } else {
                fieldsToRender = providerFields.filter(
                  (f) => f.name !== "apple_secret"
                );
              }
            }

            return fieldsToRender.map((field) => {
              if (
                provider.class === "microsoft" &&
                field.name === "microsoft_tenant_id" &&
                settings["microsoft_tenant_type"] !== "tenant_id"
              ) {
                return null;
              }
              return (
                <SettingField
                  key={field.name}
                  name={field.name}
                  label={field.label}
                  type={field.type}
                  value={settings[field.name] || ""}
                  onChange={(name, value) =>
                    handleProviderSettingChange(provider.class, name, value)
                  }
                  options={field.options}
                  readOnly={field.readOnly}
                  disabled={field.disabled}
                  desc3={field.desc}
                  desc2={field.desc2}
                />
              );
            });
          })()}
        </>
      )}

      <div className="loginpress-provider-description">
        <h4>{__("Help Center", "loginpress-pro")}</h4>
        <div dangerouslySetInnerHTML={{ __html: provider.description }} />
      </div>
    </div>
  );
};

  // Render Providers Tab
  const renderProvidersTab = () => (
    <div className="loginpress-providers-tab">
      {selectedProvider ? (
        <>
          <span 
		  aria-label={__('Choose another provider', 'loginpress-pro')}
            className="button loginpress-back-button"
            onClick={handleBackToProviders}
          >
            <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7.84626 0.250834C7.50119 -0.0836112 6.9419 -0.0836112 6.59682 0.250834L0.260737 6.39178C0.239204 6.41265 0.218776 6.43459 0.199452 6.4576C0.193379 6.46455 0.18841 6.47204 0.182336 6.47954C0.170742 6.49452 0.159147 6.50897 0.148105 6.52449C0.140375 6.53572 0.13375 6.5475 0.126573 6.55873C0.118843 6.57104 0.111113 6.58335 0.103936 6.59619C0.0967581 6.60903 0.0906849 6.62188 0.0846117 6.63525C0.0785384 6.64756 0.0724651 6.65933 0.066944 6.67164C0.0608707 6.68555 0.0564538 6.69947 0.0514848 6.71338C0.0470678 6.72569 0.0420989 6.738 0.0382341 6.7503C0.033265 6.76582 0.0299523 6.78134 0.0260875 6.79632C0.0233269 6.80809 0.020014 6.81933 0.0172534 6.83111C0.0128365 6.85144 0.010076 6.87177 0.00786752 6.89211C0.00676329 6.8996 0.00565919 6.90656 0.00455496 6.91405C-0.00151832 6.9713 -0.00151832 7.0291 0.00455496 7.08635C0.00510708 7.08956 0.00565912 7.09277 0.00621124 7.09598C0.00897182 7.1206 0.0128366 7.14522 0.0178056 7.1693C0.019462 7.17625 0.0216704 7.18321 0.0233267 7.19017C0.0282958 7.2105 0.0332649 7.23083 0.0393381 7.25063C0.0415466 7.25759 0.0443071 7.26455 0.0470677 7.27204C0.0536931 7.2913 0.0608707 7.3111 0.0691525 7.33036C0.071913 7.33625 0.0746737 7.3416 0.0774343 7.34749C0.0868203 7.36729 0.0962062 7.38709 0.106696 7.40635C0.108905 7.4101 0.111665 7.41384 0.113874 7.41759C0.125468 7.43792 0.138167 7.45826 0.151418 7.47806C0.152522 7.47966 0.153626 7.48127 0.154731 7.48287C0.185097 7.52622 0.21988 7.56742 0.259081 7.60541L6.59793 13.749C6.77019 13.916 6.99656 14 7.22237 14C7.44819 14 7.67455 13.9165 7.84682 13.749C8.19189 13.4146 8.19189 12.8725 7.84682 12.5381L3.0158 7.85584H19.1166C19.6047 7.85584 20 7.4727 20 6.99967C20 6.52663 19.6047 6.14349 19.1166 6.14349H3.0158L7.84626 1.46126C8.19134 1.12735 8.19134 0.585279 7.84626 0.250834Z" fill="#2B3D54"/>
            </svg>
            {__('Choose another provider', 'loginpress-pro')}
          </span>
          
          {renderProviderSettings({
          provider: selectedProvider,
          settings,
          providerNotes,
          handleSettingChange: handleProviderSettingChange,
          getProviderStatus,
          loginpressReactData,
          window
        })}
        </>
       ) : (
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
          modifiers={[restrictToParentElement]}
        >
          <SortableContext
            items={sortedProviders.map(provider => provider.class)}
            strategy={rectSortingStrategy}
          >
            <div className="loginpress-providers-grid">
              {sortedProviders.map((provider) => {
                const status = getProviderStatus(provider);
                const isComingSoon = provider.soon;
                
                return (
                  <SortableItem 
                    key={provider.class} 
                    id={provider.class}
                    disabled={isComingSoon}
                  >
                    <div
                      className={`loginpress-provider-card ${isComingSoon ? 'loginpress-provider-card-static' : ''}`}
                      // onClick={!isComingSoon ? () => handleProviderSelect(provider) : undefined}
                      data-provider={provider.class}
                    >
                      <div className="loginpress-provider-head">
                        <img 
                          src={`${loginPressGlobal.socialDirPath ? loginPressGlobal.socialDirPath : loginpressPath }/assets/img/${provider.class=='gplus'?'google':provider.class}.svg`} 
                          alt={provider.name} 
                        />
                      </div>
                      <div className="loginpress-provider-foot">
                        {!isComingSoon && (
                          <>
                            <span className={`loginpress-provider-status ${status.class === 'verified' ? 'enabled' : status.class}`}>
                              {status.text}
                            </span>
                            <button 
                              className="loginpress-provider-button"
                              onClick={(e) => {
                                e.stopPropagation();
                                handleProviderSelect(provider);
                              }}
                            >
                              {__('Configure', 'loginpress-pro')}
                            </button>
                          </>
                        )}
                        {isComingSoon && (
                          <span className="loginpress-provider-comingsoon">
                            {__('Coming Soon', 'loginpress-pro')}
                          </span>
                        )}
                      </div>
                    </div>
                  </SortableItem>
                );
              })}
              
              {/* Static card (not draggable) */}
              <div className="loginpress-provider-card loginpress-provider-card-static">
                <div className="loginpress-provider-head">
                  <h3>{__('Looking for Another Provider?', 'loginpress-pro')}</h3>
                </div>
                <div className="loginpress-provider-foot">
                  <p>
                    {__('Need a provider not listed here?', 'loginpress-pro')}<br />
                    {__('Let us know!', 'loginpress-pro')}
                  </p>
                  <a 
                    className="btn" 
                    href="https://loginpress.pro/contact/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=custom-request" 
                    target="_blank" 
                    rel="noopener noreferrer"
                  >
                    {__('Contact Us', 'loginpress-pro')}
                  </a>
                </div>
              </div>
            </div>
          </SortableContext>
        </DndContext>
      )}
    </div>
  );

  if (loading) {
    return <div className="skeleton-content">
    <div className="skeleton-heading"></div>

    <div className="skeleton-paragraph-group">
      <div className="skeleton-paragraph"></div>
      <div className="skeleton-paragraph"></div>
      <div className="skeleton-paragraph short"></div>
    </div>
    <div className="skeleton-paragraph-group">
      <div className="skeleton-paragraph"></div>
    </div>

    <div className="skeleton-big-box"></div>
	<div className="skeleton-button"></div>
</div>;
  }

  return (
    <>
      <InfoBox
        heading={__("Social Login", "loginpress-pro")}
        description={__(
          "Social Login add-on allows your users to log in and register using their Facebook, Google, X (Twitter) accounts and more. By integrating these social media platforms into your login system, you can eliminate spam and bot registrations effectively.",
          "loginpress-pro"
        )}
        videoUrl="https://www.youtube.com/watch?v=45S3i9PJhLA"
		className="loginpress-less-padding"
      />
      <div className="inside">
        <div className="loginpress-social-login-tab-wrapper">
          <button
            className={`loginpress-social-login-tab ${
              activeTab === "settings" ? "active" : ""
            }`}
            onClick={() => {
              setActiveTab("settings");
              setShowSaveButton(true);
            }}
          >
            {__("Settings", "loginpress-pro")}
          </button>
          <button
            className={`loginpress-social-login-tab ${
              activeTab === "styles" ? "active" : ""
            }`}
            onClick={() => {
              setActiveTab("styles");
              setShowSaveButton(true);
            }}
          >
            {__("Styles", "loginpress-pro")}
          </button>
          <button
            className={`loginpress-social-login-tab ${
              activeTab === "providers" ? "active" : ""
            }`}
            onClick={() => {
              setActiveTab("providers");
              setShowSaveButton(!!selectedProvider);
            }}
          >
            {__("Providers", "loginpress-pro")}
          </button>
        </div>

        <div className="loginpress-tab-socialloginsettings">
          {activeTab === "settings" && renderSettingsTab()}
          {activeTab === "styles" && renderStylesTab()}
          {activeTab === "providers" && renderProvidersTab()}
        </div>

        {(showSaveButton || activeTab !== "providers") && (
          <div className="loginpress-form-group">
            {(function () {
              const isAppleSelected =
                selectedProvider && selectedProvider.class === "apple";
              const isAppleVerified =
                isAppleSelected &&
                settings["apple_status"] === "verified" &&
                !!settings["apple_secret"];
              const showReconfigure =
                isAppleVerified &&
                !appleReconfigure &&
                activeTab === "providers";
              const buttonLabel = loading
                ? __("Saving...", "loginpress-pro")
                : showReconfigure
                ? __("Reconfigure", "loginpress-pro")
                : __("Save Changes", "loginpress-pro");
              const onClickHandler = showReconfigure
                ? () => setAppleReconfigure(true)
                : saveSettings;
              return (
                <button
                  className="button button-primary"
                  onClick={onClickHandler}
                  disabled={loading}
                >
                  {buttonLabel}
                </button>
              );
            })()}
            {saveChanges.text && (
              <div className={`message ${saveChanges.type}`}>
                <p>{saveChanges.text}</p>
                <span
                  className="close-me"
                  onClick={() => setSaveChanges({ text: "", type: "" })}
                ><i className="dashicons dashicons-no-alt"></i></span>
              </div>
            )}
          </div>
        )}
      </div>
    </>
  );
};

export default SocialLoginSettings;
const style = document.createElement('style');
style.textContent = `
  .loginpress-providers-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 20px;
    
}

.loginpress-provider-card {
  position: relative;
  border-radius: 5px;
  transition: all 0.3s ease;
  width: 100%;
  background-color: rgba(242, 243, 255, 1) !important;
  border: 1px solid rgba(210, 221, 242, 1) !important;
  padding: 15px;
  box-sizing: border-box;
  cursor: grab !important;
    height: auto !important;
    min-height: 117px;
    
}

.loginpress-provider-card-static {
  cursor: auto !important;
    background: #EAEDF4 !important;
    border-color: #EAEDF4 !important;
}

.loginpress-provider-card:hover {
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.loginpress-provider-head {
  text-align: center;
/*   margin-bottom: 15px; */
}

.loginpress-provider-head img {
  max-width: 100%;
  height: auto;
  max-height: 50px;
}

.loginpress-provider-foot {
/*   text-align: center; */
}

.drag-handle {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  cursor: grab;
}

.drag-handle:hover {
  color: #555;
}

.loginpress-provider-status {
  display: block;
  font-size: 14px;
}

.loginpress-provider-comingsoon {
  display: inline-block;
  background: #f0f0f1;
  color: #757575;
  padding: 5px 10px;
  border-radius: 3px;
  font-size: 12px;
}

/* Drag states */
[aria-pressed="true"] .loginpress-provider-card {
  box-shadow: 0 0 0 2px #2271b1;
}

/* Placeholder during drag */
.sortable-ghost {
  opacity: 0.5;
  background: #f0f0f1;
  border: 2px dashed #2271b1;
}
.dashicons-screenoptions {
  position: absolute;
  top: 10px;
  inset-inline-end: 10px;
  color: #ccc;
  cursor: move;
  z-index: 2;
}
#wpcontent .loginpress-provider-foot p {
    color: #454D5F;
    margin: 0;
}
`;
document.head.appendChild(style);
