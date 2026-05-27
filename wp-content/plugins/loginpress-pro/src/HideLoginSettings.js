import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import './HideLoginSettings.css';
import InfoBox from './InfoBox';
import SettingField from './SettingField';
const HideLogin = () => {
  const activeAddons = window.loginpressProData?.activeAddons || [];

  if (!activeAddons.includes('hide-login')) {
    return;
  }
  const [settings, setSettings] = useState({
    rename_login_slug: '',
    is_rename_send_email: 'off',
    rename_email_send_to: ''
  });
  const [loading, setLoading] = useState(true);
  const [copied, setCopied] = useState(false);
  const [message, setMessage] = useState({ text: '', type: 'success' });
  const [savedSlug, setSavedSlug] = useState('');

  // Fetch initial settings
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await apiFetch({
          path: '/loginpress/v1/hidelogin-settings',
          method: 'GET'
        });
        setSettings(response);
        setSavedSlug(response.rename_login_slug || '');
        setLoading(false);
      } catch (error) {
        setLoading(false);
      }
    };

    fetchSettings();
  }, []);

  // Handle input changes
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    const newValue = type === 'checkbox' ? (checked ? 'on' : 'off') : value;
    
    setSettings(prev => ({
      ...prev,
      [name]: newValue
    }));
  };
  const handleSettingChange = (name, value) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  // Generate random slug
  const generateRandomSlug = () => {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    while (result.length < 12) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setSettings(prev => ({
      ...prev,
      rename_login_slug: result
    }));
  };

  // Reset login slug
  const resetLoginSlug = async () => {
    try {
      await apiFetch({
        path: '/loginpress/v1/reset-login-slug',
        method: 'POST',
        headers: {
            'X-WP-Nonce': loginpressReactData.nonce
          }
      });
      setSettings(prev => ({
        ...prev,
        rename_login_slug: ''
      }));
      setSavedSlug('');
      setMessage({ text: __( 'Login slug has been reset', 'loginpress' ), type: 'success' });
    } catch (error) {
      setMessage({ text: __( 'Failed to reset login slug', 'loginpress' ), type: 'error' });
    } finally {
		// setLoading(false);
		setTimeout( function(){
			setMessage({text: '', type: ''})
		}, 3000);
	  }
  };

  // Save settings
  const saveSettings = async (e) => {
    e.preventDefault();
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/hidelogin-settings-update',
        method: 'POST',
        data: settings,
        headers: {
            'X-WP-Nonce': loginpressReactData.nonce
          }
      });
      // Only update savedSlug after successful save to database
      setSavedSlug(settings.rename_login_slug || '');
      setMessage({ text: __( 'Settings saved successfully.', 'loginpress-pro' ), type: 'success' });
      if (settings.is_rename_send_email === 'on' && settings.rename_login_slug) {
        setMessage({ text: __( 'Settings saved successfully and email sent.', 'loginpress-pro' ), type: 'success' });
      }
    } catch (error) {
      setMessage({ text: 'Failed to save settings', type: 'error' });
    } finally {
		setTimeout( function(){
			setMessage({text: '', type: ''})
		}, 3000);
    }
  };

  // Copy to clipboard
  const copyToClipboard = () => {
    const url = settings.rename_login_slug 
      ? `${loginpress_pro_local.homeUrl}/${settings.rename_login_slug}`
      : `${loginpress_pro_local.homeUrl}/wp-login.php`;
    
    // Check if clipboard API is available
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(() => {
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      }).catch(() => {
        // Fallback for clipboard API failure
        fallbackCopyToClipboard(url);
      });
    } else {
      // Fallback for browsers without clipboard API
      fallbackCopyToClipboard(url);
    }
  };

  // Fallback copy method using document.execCommand
  const fallbackCopyToClipboard = (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      const successful = document.execCommand('copy');
      if (successful) {
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      }
    } catch (err) {
    }
    
    document.body.removeChild(textArea);
  };

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
      heading={__('Hide Login', 'loginpress-pro')}
      description={__('The Hide Login add-on for LoginPress lets you change the login page URL according to your preference. It will give a hard time to spammers who keep hitting your login page, preventing Brute force attacks.', 'loginpress-pro')}
      videoUrl="https://www.youtube.com/watch?v=FSE2BH_biZg"
    />
    <div className="loginpress-hidelogin-container">

      <form onSubmit={saveSettings}>
        {/* Rename Login Slug */}
        <div className="loginpress-setting-field">
          <label className="loginpress-setting-label" htmlFor="rename_login_slug">
            {__('Rename Login Slug', 'loginpress-pro')}
          </label>
          <div className='loginpress-hide-login-field'>
			<div className="loginpress-hidelogin-slug-wrapper">
            <span className="loginpress-url-prefix">
              {loginpress_pro_local.homeUrl}/
            </span>
            <input
              type="text"
              id="rename_login_slug"
              name="rename_login_slug"
              value={settings.rename_login_slug}
              onChange={handleChange}
              className="regular-text"
            />
                          <button 
                type="button" 
                className="button loginpress-copy-button"
				data-copied={copied ? 'Copied' : 'Copy'}
                onClick={copyToClipboard}
                title={__('Copy to clipboard', 'loginpress-pro')}
              >
                {!copied ? (
                  <svg className="hidelogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clipPath="url(#clip0_27_294)">
                      <path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"/>
                      <path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"/>
                    </g>
                    <defs>
                      <clipPath id="clip0_27_294">
                        <rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"/>
                      </clipPath>
                    </defs>
                  </svg>
                ) : (
                  <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                    width="18px" height="13.5px" viewBox="0 0 18 13.5" style={{enableBackground: 'new 0 0 18 13.5'}} xmlSpace="preserve">
                    <path className="st0" fill="#1F693F" d="M15.6,0L7,8.6L2.4,4L0,6.5l7,7l11-11L15.6,0z"/>
                  </svg>
                )}
              </button>
          </div>

        {/* Generate/Reset Buttons */}
        <div className="loginpress-button-group">
          <button
            type="button"
            className="button"
            onClick={generateRandomSlug}
          >
            {__('Generate Slug (Randomly)', 'loginpress-pro')}
          </button>
          {savedSlug && (
            <button
              type="button"
              className="button button-primary"
              onClick={resetLoginSlug}
            >
              {__('Reset Login Slug', 'loginpress-pro')}
            </button>
          )}
        </div>
          <p className="loginpress-description">
            {savedSlug ? (
              <>
                {__('Here is your login page now:', 'loginpress-pro')}{' '}
                <a 
                  href={`${loginpress_pro_local.homeUrl}/${savedSlug }`} 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  {loginpress_pro_local.homeUrl}/{savedSlug}
                </a>{' '}
                {__('Bookmark this page!', 'loginpress-pro')}
              </>
            ) : (
              <>
                {__('Your default login page:', 'loginpress-pro')}{' '}
                <a 
                  href={`${loginpress_pro_local.homeUrl}/wp-login.php`} 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  {loginpress_pro_local.homeUrl}/wp-login.php
                </a>{' '}
                {__('Bookmark this page!', 'loginpress-pro')}
              </>
            )}
          </p>
		  </div>
        </div>

        {/* {Send Email Checkbox Field} */}
        <div className="loginpress-setting-field loginpress-setting-field-checkbox">
          <label className="loginpress-setting-label" htmlFor="is_rename_send_email">
            {__('Send Email', 'loginpress-pro')}
          </label>
          <div className="loginpress-setting-input">
            <span className="loginpress-checkbox">
              <input
                id="is_rename_send_email"
                name="is_rename_send_email"
                type="checkbox"
                checked={settings.is_rename_send_email === 'on'}
                onChange={handleChange}
              />
            </span>
            {/* Show message when email option is disabled */}
            {settings.is_rename_send_email === 'off' ? (
              <label className="loginpress-label-des desc2">
                {__('Send email after changing the wp-login.php slug?', 'loginpress-pro')}
              </label>
              ) : (
              <label className="loginpress-label-des desc2">
                {__('Email will be sent to the address defined below.', 'loginpress-pro')}
              </label>
            )}
          </div>
        </div>

        {/* Conditional Email Address Field */}
        {settings.is_rename_send_email === 'on' && (
          
          <SettingField
            name="rename_email_send_to"
            label={__('Email Address', 'loginpress-pro')}
            type="tags"
            value={settings.rename_email_send_to || ''}
            onChange={handleSettingChange}
            placeholder="admin@example.com"
            tagsSeparator=", "
            tagType="email"
            desc2= {__('Press Enter to add email address(es) to receive email.', 'loginpress-pro')}
          />
          
        )}

        {/* Submit Button */}
        <div className="loginpress-form-group">
          <button
            type="submit"
            className="button button-primary"
            disabled={loading}
          >
            {loading ? __('Saving...', 'loginpress-pro') : __('Save Changes', 'loginpress-pro')}
          </button>
		  {message.text &&(
			<>
        <div className={`message ${message.type}`}>
          <p>{message.text}</p>
          <span className='close-me' onClick={() => setMessage({text: '', type: ''})}><i className="dashicons dashicons-no-alt"></i></span>
        </div>
		</>
		)}
        </div>
      </form>
    </div>
	
    </>
  );
};

export default HideLogin;