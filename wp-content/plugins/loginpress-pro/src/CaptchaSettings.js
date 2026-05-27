import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import SettingField from './SettingField';
import TurnstileWidget from './TurnstileWidget';
import HcaptchaWidget from './HcaptchaWidget';
import RecaptchaWidget from './RecaptchaWidget';
import RecaptchaV3Widget from './RecaptchaV3Widget';
import InfoBox from './InfoBox';
import './CaptchaSettings.css'

const CaptchaSettings = () => {
	const [settings, setSettings] = useState({
		enable_captchas: 'off',
		captchas_type: 'type_recaptcha',
		recaptcha_type: 'v2-robot',
		site_key: '',
		secret_key: '',
		site_key_v3: '',
		secret_key_v3: '',
		good_score: '0.5',
		captcha_theme: 'light',
		captcha_language: 'en',
		v2_robot_verified: '',
		v3_verified: '',
		v3_ever_validated: '',
		captcha_enable: { login_form: 'login_form' }, // Object format to match option data
		hcaptcha_type: 'normal',
		hcaptcha_site_key: '',
		hcaptcha_secret_key: '',
		hcaptcha_theme: 'light',
		hcaptcha_language: 'en',
		hcaptcha_verified: '',
		hcaptcha_enable: { login_form: 'login_form' }, // Object format
		site_key_cf: '',
		secret_key_cf: '',
		captcha_enable_cf: { login_form: 'login_form' }, // Object format
		cf_theme: 'light',
	  });

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });
  const [cfValidated, setCfValidated] = useState(settings.validate_cf === 'on');
  const [triggerValidation, setTriggerValidation] = useState(false);
  const [hcaptchaValidated , setHcaptchaValidated] = useState(settings.validate_cf === 'on');
  const [triggerHcaptchaValidation, setTriggerHcaptchaValidation] = useState(false);
  const [recaptchaValidated  , setRecaptchaValidated] = useState(settings.hcaptcha_verified === 'on');
  const [triggerRecaptchaValidation , setTriggerRecaptchaValidation] = useState(false);
  const [recaptchaV3Validated, setRecaptchaV3Validated] = useState(settings.v3_verified === 'on');
  const [triggerRecaptchaV3Validation, setTriggerRecaptchaV3Validation] = useState(false);
  const [hasUserInteracted, setHasUserInteracted] = useState(false);
  const [showV3ValidationNotice, setShowV3ValidationNotice] = useState(false);
  // Apply recaptcha to options
  const applyRecaptchaTo = [
    { value: 'login_form', label: __('Login Form', 'loginpress-pro') },
    { value: 'lostpassword_form', label: __('Lost Password Form', 'loginpress-pro') },
    { value: 'register_form', label: __('Register Form', 'loginpress-pro') },
    { value: 'comment_form_defaults', label: __('Comments Section', 'loginpress-pro') }
  ];

  // Score options for reCAPTCHA v3
  const scoreOptions = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0].map(score => ({
    value: score.toString(),
    label: score.toString()
  }));

  // Theme options
  const themeOptions = [
    { value: 'light', label: __('Light', 'loginpress-pro') },
    { value: 'dark', label: __('Dark', 'loginpress-pro') }
  ];

  // Language options
  const languageOptions = [
    { value: 'ar', label: __('Arabic', 'loginpress-pro') },
    { value: 'af', label: __('Afrikaans', 'loginpress-pro') },
    { value: 'am', label: __('Amharic', 'loginpress-pro') },
    { value: 'hy', label: __('Armenian', 'loginpress-pro') },
    { value: 'az', label: __('Azerbaijani', 'loginpress-pro') },
    { value: 'eu', label: __('Basque', 'loginpress-pro') },
    { value: 'bn', label: __('Bengali', 'loginpress-pro') },
    { value: 'bg', label: __('Bulgarian', 'loginpress-pro') },
    { value: 'ca', label: __('Catalan', 'loginpress-pro') },
    { value: 'zh-HK', label: __('Chinese (HongKong)', 'loginpress-pro') },
    { value: 'zh-CN', label: __('Chinese (Simplified)', 'loginpress-pro') },
    { value: 'zh-TW', label: __('Chinese (Traditional)', 'loginpress-pro') },
    { value: 'hr', label: __('Croatian', 'loginpress-pro') },
    { value: 'cs', label: __('Czech', 'loginpress-pro') },
    { value: 'da', label: __('Danish', 'loginpress-pro') },
    { value: 'nl', label: __('Dutch', 'loginpress-pro') },
    { value: 'en-GB', label: __('English (UK)', 'loginpress-pro') },
    { value: 'en', label: __('English (US)', 'loginpress-pro') },
    { value: 'fil', label: __('Filipino', 'loginpress-pro') },
    { value: 'fi', label: __('Finnish', 'loginpress-pro') },
    { value: 'fr', label: __('French', 'loginpress-pro') },
    { value: 'fr-CA', label: __('French (Canadian)', 'loginpress-pro') },
    { value: 'gl', label: __('Galician', 'loginpress-pro') },
    { value: 'ka', label: __('Georgian', 'loginpress-pro') },
    { value: 'de', label: __('German', 'loginpress-pro') },
    { value: 'de-AT', label: __('German (Austria)', 'loginpress-pro') },
    { value: 'de-CH', label: __('German (Switzerland)', 'loginpress-pro') },
    { value: 'el', label: __('Greek', 'loginpress-pro') },
    { value: 'gu', label: __('Gujarati', 'loginpress-pro') },
    { value: 'iw', label: __('Hebrew', 'loginpress-pro') },
    { value: 'hi', label: __('Hindi', 'loginpress-pro') },
    { value: 'hu', label: __('Hungarian', 'loginpress-pro') },
    { value: 'is', label: __('Icelandic', 'loginpress-pro') },
    { value: 'id', label: __('Indonesian', 'loginpress-pro') },
    { value: 'it', label: __('Italian', 'loginpress-pro') },
    { value: 'ja', label: __('Japanese', 'loginpress-pro') },
    { value: 'kn', label: __('Kannada', 'loginpress-pro') },
    { value: 'ko', label: __('Korean', 'loginpress-pro') },
    { value: 'lo', label: __('Laothian', 'loginpress-pro') },
    { value: 'lv', label: __('Latvian', 'loginpress-pro') },
    { value: 'lt', label: __('Lithuanian', 'loginpress-pro') },
    { value: 'ms', label: __('Malay', 'loginpress-pro') },
    { value: 'ml', label: __('Malayalam', 'loginpress-pro') },
    { value: 'mr', label: __('Marathi', 'loginpress-pro') },
    { value: 'mn', label: __('Mongolian', 'loginpress-pro') },
    { value: 'no', label: __('Norwegian', 'loginpress-pro') },
    { value: 'fa', label: __('Persian', 'loginpress-pro') },
    { value: 'pl', label: __('Polish', 'loginpress-pro') },
    { value: 'pt', label: __('Portuguese', 'loginpress-pro') },
    { value: 'pt-BR', label: __('Portuguese (Brazil)', 'loginpress-pro') },
    { value: 'pt-PT', label: __('Portuguese (Portugal)', 'loginpress-pro') },
    { value: 'ro', label: __('Romanian', 'loginpress-pro') },
    { value: 'ru', label: __('Russian', 'loginpress-pro') },
    { value: 'sr', label: __('Serbian', 'loginpress-pro') },
    { value: 'si', label: __('Sinhalese', 'loginpress-pro') },
    { value: 'sk', label: __('Slovak', 'loginpress-pro') },
    { value: 'sl', label: __('Slovenian', 'loginpress-pro') },
    { value: 'es', label: __('Spanish', 'loginpress-pro') },
    { value: 'es-419', label: __('Spanish (Latin America)', 'loginpress-pro') },
    { value: 'sw', label: __('Swahili', 'loginpress-pro') },
    { value: 'sv', label: __('Swedish', 'loginpress-pro') },
    { value: 'ta', label: __('Tamil', 'loginpress-pro') },
    { value: 'te', label: __('Telugu', 'loginpress-pro') },
    { value: 'th', label: __('Thai', 'loginpress-pro') },
    { value: 'tr', label: __('Turkish', 'loginpress-pro') },
    { value: 'ur', label: __('Urdu', 'loginpress-pro') },
    { value: 'uk', label: __('Ukrainian', 'loginpress-pro') },
    { value: 'vi', label: __('Vietnamese', 'loginpress-pro') },
    { value: 'zu', label: __('Zulu', 'loginpress-pro') }
  ];

  // CAPTCHA type options - will be fetched from API
  const [captchaTypeOptions, setCaptchaTypeOptions] = useState([
    { value: 'type_recaptcha', label: __('Google reCAPTCHA', 'loginpress-pro') },
    { value: 'type_hcaptcha', label: __('hCaptcha', 'loginpress-pro') },
    { value: 'type_cloudflare', label: __('Cloudflare Turnstile', 'loginpress-pro') }
  ]);

  // reCAPTCHA version options
  const recaptchaVersionOptions = [
    { value: 'v2-robot', label: __("V2 I'm not robot", 'loginpress-pro') },
    { value: 'v3', label: __('V3', 'loginpress-pro') }
  ];

  // hCaptcha type options
  const hcaptchaTypeOptions = [
    { value: 'normal', label: __('Normal', 'loginpress-pro') },
    { value: 'compact', label: __('Compact', 'loginpress-pro') },
    { value: 'invisible', label: __('Invisible', 'loginpress-pro') }
  ];

  // Fetch captcha type options from API
  const fetchCaptchaOptions = async () => {
    try {
      const response = await apiFetch({ path: '/loginpress/v1/captcha-options' });
      if (response && Array.isArray(response)) {
        setCaptchaTypeOptions(response);

        // Check if current selected captcha type is still available
        const currentType = settings.captchas_type;
        const availableTypes = response.map(option => option.value);

        if (currentType && !availableTypes.includes(currentType)) {
          // If current type is not available, switch to the first available option
          const newType = availableTypes[0];
          if (newType) {
            setSettings(prev => ({
              ...prev,
              captchas_type: newType
            }));
          }
        }
      }
    } catch (error) {
    }
  };

  // Fetch settings on component mount
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await apiFetch({ path: '/loginpress/v1/captcha-settings' });
        if (response) {
          setSettings(response);
        }
        if(response.validate_cf === 'on'){
          setCfValidated(true);
        }
        if (response.hcaptcha_verified === 'on'){
          setHcaptchaValidated(true);
        }
        if (response.v2_robot_verified === 'on'){
          setRecaptchaValidated(true);
        }
        if (response.v3_verified === 'on'){
          setRecaptchaV3Validated(true);
        }
        
        // Check if V3 validation notice should be shown
        // Show only if: V3 keys exist, not verified, and never validated before
        if (
          response.site_key_v3 && 
          response.secret_key_v3 && 
          response.v3_verified !== 'on' && 
          response.v3_ever_validated !== 'on' &&
          response.recaptcha_type === 'v3' &&
          response.captchas_type === 'type_recaptcha'
        ) {
          setShowV3ValidationNotice(true);
        }
        
        if (response.enable_captchas === 'on') {
          localStorage.setItem('loginpress_captcha_enabled', true);
        } else {
          localStorage.setItem('loginpress_captcha_enabled', false);
        }
      } catch (error) {
        setMessage({ type: 'error', text: __('Failed to load settings', 'loginpress-pro') });
      } finally {
        setLoading(false);
      }
    };

    fetchSettings();
    fetchCaptchaOptions();
  }, []);
  // Handle field changes
  // Added handleChange to maintain PHP-style format for multicheck fields
  const handleChange = (name, value) => {
    // Set user interaction flag when any change is made
    setHasUserInteracted(true);
    
    if (
      (name === 'site_key_cf' && value !== settings.site_key_cf) ||
      (name === 'secret_key_cf' && value !== settings.secret_key_cf)
    ) {
      setTriggerValidation(true);
      setCfValidated(false);
    }
    if (
      (name === 'hcaptcha_site_key' && value !== settings.hcaptcha_site_key) ||
      (name === 'hcaptcha_secret_key' && value !== settings.hcaptcha_secret_key)
    ) {
      setTriggerHcaptchaValidation(true);
      setHcaptchaValidated(false);
    }
    if (
      (name === 'site_key' && value !== settings.site_key) ||
      (name === 'secret_key' && value !== settings.secret_key)
    ) {
      setTriggerRecaptchaValidation(true);
      setRecaptchaValidated(false);
    }
    if (
      (name === 'site_key_v3' && value !== settings.site_key_v3) ||
      (name === 'secret_key_v3' && value !== settings.secret_key_v3)
    ) {
      setTriggerRecaptchaV3Validation(true);
      setRecaptchaV3Validated(false);
    }

    setSettings(prev => {
      
      // Check if this is a multicheck field (ends with '_enable' or starts with 'captcha_enable')
      const isMulticheckField = name.endsWith('_enable') || name.startsWith('captcha_enable');
      
      // If it's a multicheck field and the value is an array, convert to PHP-style format
      var newValue = isMulticheckField && Array.isArray(value)
        ? value.reduce((acc, val) => {
            acc[val] = val;
            return acc;
          }, {})
        : value;
      return {
        ...prev,
        [name]: newValue
      };
    });
  };

  // Convert PHP-style format to array for rendering
  const getMulticheckValue = (fieldName) => {
    const value = settings[fieldName];
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'object' && value !== null) {
      return Object.keys(value);
    }
    return [];
  };

  // Save settings
  const saveSettings = async (e) => {
    e.preventDefault();
    setSaving(true);
    if (settings.enable_captchas === 'on'){
      if (settings.captchas_type === 'type_cloudflare' && !cfValidated){
        setMessage({ type: 'error', text: __('Turnstile not validated', 'loginpress-pro') });
        setSaving(false);
            // Clear after 3 seconds
        setTimeout(() => {
          setMessage('');
        }, 3000);
        return;
      } else if (settings.captchas_type === 'type_hcaptcha' && !hcaptchaValidated){
        setMessage({ type: 'error', text: __('Hcaptcha not validated', 'loginpress-pro') });
        setSaving(false);
        // Clear after 3 seconds
        setTimeout(() => {
          setMessage('');
        }, 3000);
        return;
      } else if (settings.captchas_type === 'type_recaptcha'){
        if (settings.recaptcha_type === 'v2-robot' && !recaptchaValidated){
          setMessage({ type: 'error', text: __('Recaptcha not validated', 'loginpress-pro') });
          setSaving(false);
          // Clear after 3 seconds
          setTimeout(() => {
            setMessage('');
          }, 3000);
          return;
        } else if (settings.recaptcha_type === 'v3' && !recaptchaV3Validated){
          setMessage({ type: 'error', text: __('Recaptcha V3 not validated', 'loginpress-pro') });
          setSaving(false);
          // Clear after 3 seconds
          setTimeout(() => {
            setMessage('');
          }, 3000);
          return;
        }
      }
      
    }
    
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/captcha-settings',
        method: 'POST',
        data: {
          ...settings,
          validate_cf: cfValidated ? 'on' : 'off',
          hcaptcha_verified: hcaptchaValidated ? 'on' : 'off',
          v2_robot_verified: recaptchaValidated ? 'on' : 'off',
          v3_verified: recaptchaV3Validated ? 'on' : 'off',
          v3_ever_validated: recaptchaV3Validated ? 'on' : settings.v3_ever_validated
        }
      });

      if (response.success) {
        if (settings.enable_captchas === 'on') {
          localStorage.setItem('loginpress_captcha_enabled', true);
        } else {
          localStorage.setItem('loginpress_captcha_enabled', false);
        }
        // Hide V3 notice after successful save if V3 is validated
        if (recaptchaV3Validated) {
          setShowV3ValidationNotice(false);
        }
        setMessage({ type: 'success', text: __('Settings saved successfully!', 'loginpress-pro') });
      } else {
        setMessage({ type: 'error', text: response.message || __('Failed to save settings', 'loginpress-pro') });
      }
    } catch (error) {
      setMessage({ type: 'error', text: __('Failed to save settings', 'loginpress-pro') });
    } finally {
      setSaving(false);
	  setTimeout(function(){
		setMessage({text: '', type: ''})
	  }, 2000)
    }
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
      heading={__('Captchas', 'loginpress-pro')}
      description={__('The LoginPress CAPTCHA feature lets you easily integrate different types of CAPTCHA services into your login and registration forms. CAPTCHA types offered include Google reCAPTCHA, hCAPTCHA, and other widely used CAPTCHA services. This feature helps prevent spam, bot attacks, and authorized access, ensuring a more secure user experience.', 'loginpress-pro')}
      videoUrl="https://www.youtube.com/watch?v=26dUFdX2srU"
    />
    
    {/* V3 Validation Notice */}
    {showV3ValidationNotice && (
      <div className="captcha-notify-container">
        <div className="captcha-notify-description">
          <h4>{__('Validate Your reCAPTCHA V3 Settings', 'loginpress-pro')}</h4>
          <p>{__('To ensure seamless functionality, please validate your reCAPTCHA V3 settings. Existing setups will remain unaffected, but verification is required for continued use.', 'loginpress-pro')}</p>
        </div>
      </div>
    )}
    
    <div className="loginpress-captcha-settings">

      <form onSubmit={saveSettings}>
        {/* Enable/Disable CAPTCHAs */}
        <SettingField
          type="checkbox"
          name="enable_captchas"
          label={__('Enable/Disable CAPTCHAs', 'loginpress-pro')}
          value={settings.enable_captchas === 'on'}
          onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
          desc={__('Enable to add CAPTCHAs to your forms.', 'loginpress-pro')}
        />

        {/* Show CAPTCHA settings only if enabled */}
        {settings.enable_captchas === 'on' && (
          <>
          {/* Select Captcha Type */}
          <SettingField
            type="select"
            name="captchas_type"
            label={__('Select Captcha', 'loginpress-pro')}
            value={settings.captchas_type}
            options={captchaTypeOptions}
            onChange={handleChange}
            desc2={__('Choose CAPTCHA from the options above.', 'loginpress-pro')}
          />

          {/* Conditional rendering based on selected CAPTCHA type */}
          {settings.captchas_type === 'type_recaptcha' && (
            <>
              {/* reCAPTCHA Version */}
              <SettingField
                type="select"
                name="recaptcha_type"
                label={__('reCAPTCHA Version', 'loginpress-pro')}
                value={settings.recaptcha_type}
                options={recaptchaVersionOptions}
                onChange={handleChange}
                desc2={__('Select the type of reCAPTCHA', 'loginpress-pro')}
              />

              {/* reCAPTCHA Keys */}
              {settings.recaptcha_type === 'v2-robot' && (
                <>
                <div className={`lp-recaptcha-validation ${settings.site_key && settings.secret_key && (recaptchaValidated && !triggerRecaptchaValidation ? 'lp-validated' : 'lp-not-validated')}`}>
                <SettingField
                  type="text"
                  name="site_key"
                  label={__('Site Key', 'loginpress-pro')}
                  value={settings.site_key}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">{__('reCAPTCHA', 'loginpress-pro')}</a>
                      {__(' Site Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right site key for this domain.', 'loginpress-pro')}
                />
              </div>
              <div className={`lp-recaptcha-validation ${settings.site_key && settings.secret_key && settings.recaptcha_type === 'v2-robot' && (recaptchaValidated && !triggerRecaptchaValidation ? 'lp-validated' : 'lp-not-validated')}`}>
                <SettingField
                  type="text"
                  name="secret_key"
                  label={__('Secret Key', 'loginpress-pro')}
                  value={settings.secret_key}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">{__('reCAPTCHA', 'loginpress-pro')}</a>
                      {__(' Secret Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right site key for this domain.', 'loginpress-pro')}
                />
              </div>
                </>
              )}
              

              {settings.recaptcha_type === 'v3' && (
                <>
                <div className={`lp-recaptcha-v3-validation ${settings.site_key_v3 && settings.secret_key_v3 && (recaptchaV3Validated && !triggerRecaptchaV3Validation ? 'lp-validated' : 'lp-not-validated')} ${settings.site_key_v3 && settings.secret_key_v3 && showV3ValidationNotice ? 'lp-warning' : ''}`}>
                <SettingField
                  type="text"
                  name="site_key_v3"
                  label={__('Site Key', 'loginpress-pro')}
                  value={settings.site_key_v3}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">{__('reCAPTCHA', 'loginpress-pro')}</a>
                      {__(' Site Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right site key for this domain.', 'loginpress-pro')}
                />
                </div>
                <div className={`lp-recaptcha-v3-validation ${settings.site_key_v3 && settings.secret_key_v3 && (recaptchaV3Validated && !triggerRecaptchaV3Validation ? 'lp-validated' : 'lp-not-validated')} ${settings.site_key_v3 && settings.secret_key_v3 && showV3ValidationNotice ? 'lp-warning' : ''}`}>
                <SettingField
                  type="text"
                  name="secret_key_v3"
                  label={__('Secret Key', 'loginpress-pro')}
                  value={settings.secret_key_v3}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">{__('reCAPTCHA', 'loginpress-pro')}</a>
                      {__(' Secret Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right secret key for this domain.', 'loginpress-pro')}
                />
                </div>
                {settings.captchas_type === 'type_recaptcha' && settings.recaptcha_type === 'v3' && triggerRecaptchaV3Validation && (
                  <div className='loginpress-setting-field recaptcha-v3-validation-prograssbar'>
                    <label className="loginpress-setting-label"></label>
                    <RecaptchaV3Widget
                      siteKey={settings.site_key_v3}
                      secretKey={settings.secret_key_v3}
                      show={!!settings.site_key_v3 && !!settings.secret_key_v3}
                      onValidate={(success) => {
                        setRecaptchaV3Validated(success);
							setTriggerRecaptchaV3Validation(false);
                         	setShowV3ValidationNotice(false);
                      }}
                    />
                  </div>
                )}
                </>
              )}
              {settings.captchas_type === 'type_recaptcha' && settings.recaptcha_type === 'v2-robot' && triggerRecaptchaValidation && (
                <div className='loginpress-setting-field'>
                  <label className="loginpress-setting-label"></label>
                  <RecaptchaWidget
                    siteKey={settings.site_key}
                    secretKey={settings.secret_key}
                    theme={settings.captcha_theme}
                    show={!!settings.site_key && !!settings.secret_key}
                    onValidate={(success) => {
                      setRecaptchaValidated(success);
                      if (success) {
                        setTriggerRecaptchaValidation(false);
                      }
                    }}
                  />
                </div>
              )}

              


              {/* reCAPTCHA V3 specific settings */}
              {settings.recaptcha_type === 'v3' && (
                <SettingField
                  type="select"
                  name="good_score"
                  label={__('Select reCaptcha score', 'loginpress-pro')}
                  value={settings.good_score}
                  options={scoreOptions}
                  onChange={handleChange}
                  desc2={__('Set minimum level of score to be achieved by a human user.', 'loginpress-pro')}
                />
              )}

              {/* reCAPTCHA Theme */}
              {settings.recaptcha_type === 'v2-robot' && (
                <>
                <SettingField
                  type="select"
                  name="captcha_theme"
                  label={__('Choose Theme', 'loginpress-pro')}
                  value={settings.captcha_theme}
                  options={themeOptions}
                  onChange={handleChange}
                  desc2={__('Select a theme for reCAPTCHA', 'loginpress-pro')}
                />

                {/* reCAPTCHA Language */}
                <SettingField
                  type="select"
                  name="captcha_language"
                  label={__('Choose Language', 'loginpress-pro')}
                  value={settings.captcha_language}
                  options={languageOptions}
                  onChange={handleChange}
                  desc2={__('Select a language for reCAPTCHA', 'loginpress-pro')}
                />
              </>
              )}
              {/* Enable reCAPTCHA on */}
              <SettingField
                type="multicheck"
                name="captcha_enable"
                label={__('Enable reCAPTCHA on', 'loginpress-pro')}
                value={settings.captcha_enable}
                options={applyRecaptchaTo}
                onChange={handleChange}
                desc2={__('Choose the form on which you need to apply Google reCAPTCHA.', 'loginpress-pro')}
              />
            </>
          )}

          {/* hCaptcha Settings */}
          {settings.captchas_type === 'type_hcaptcha' && (
            <>
              {/* hCaptcha Version */}
              <SettingField
                type="select"
                name="hcaptcha_type"
                label={__('hCaptcha Version', 'loginpress-pro')}
                value={settings.hcaptcha_type}
                options={hcaptchaTypeOptions}
                onChange={handleChange}
                desc2={__('Select the type of hCaptcha', 'loginpress-pro')}
              />

              {/* hCaptcha Keys */}
              <div className={`lp-hcaptcha-validation ${settings.hcaptcha_site_key && settings.hcaptcha_secret_key && (hcaptchaValidated && !triggerHcaptchaValidation ? 'lp-validated' : 'lp-not-validated')}`}>
                <SettingField
                  type="text"
                  name="hcaptcha_site_key"
                  label={__('Site Key', 'loginpress-pro')}
                  value={settings.hcaptcha_site_key}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://dashboard.hcaptcha.com/sites" target="_blank" rel="noopener noreferrer">{__('hCaptcha', 'loginpress-pro')}</a>
                      {__(' Site Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right site key.', 'loginpress-pro')}
                />
            </div>
            <div className={`lp-hcaptcha-validation ${settings.hcaptcha_site_key && settings.hcaptcha_secret_key && (hcaptchaValidated && !triggerHcaptchaValidation ? 'lp-validated' : 'lp-not-validated')}`}>
              <SettingField
                type="text"
                name="hcaptcha_secret_key"
                label={__('Secret Key', 'loginpress-pro')}
                value={settings.hcaptcha_secret_key}
                onChange={handleChange}
                desc={
                  <>
                    {__('Get ', 'loginpress-pro')}
                    <a href="https://dashboard.hcaptcha.com/sites" target="_blank" rel="noopener noreferrer">{__('hCaptcha', 'loginpress-pro')}</a>
                    {__(' Secret Key.', 'loginpress-pro')}
                  </>
                }
                desc4={__('Make sure you are adding right secret key.', 'loginpress-pro')}
              />
              </div>

              {settings.captchas_type === 'type_hcaptcha' && triggerHcaptchaValidation && (
                <div className='loginpress-setting-field'>
                  <label className="loginpress-setting-label"></label>
                <HcaptchaWidget
                  siteKey={settings.hcaptcha_site_key}
                  secretKey={settings.hcaptcha_secret_key}
                  theme={settings.hcaptcha_theme}
                  show={!!settings.hcaptcha_site_key && !!settings.hcaptcha_secret_key}
                  onValidate={(success) => {
                    setHcaptchaValidated(success);
                    if (success) {
                      setTriggerHcaptchaValidation(false);
                    }
                  }}
                />
                </div>
              )}

              {/* hCaptcha Theme */}
              <SettingField
                type="select"
                name="hcaptcha_theme"
                label={__('Choose Theme', 'loginpress-pro')}
                value={settings.hcaptcha_theme}
                options={themeOptions}
                onChange={handleChange}
                desc2={__('Select a theme for hCaptcha', 'loginpress-pro')}
              />

              {/* hCaptcha Language */}
              <SettingField
                type="select"
                name="hcaptcha_language"
                label={__('Choose Language', 'loginpress-pro')}
                value={settings.hcaptcha_language}
                options={languageOptions}
                onChange={handleChange}
                desc2={__('Select a language for hCaptcha', 'loginpress-pro')}
              />

              {/* Enable hCaptcha on */}
              <SettingField
                type="multicheck"
                name="hcaptcha_enable"
                label={__('Enable hCaptcha on', 'loginpress-pro')}
                value={settings.hcaptcha_enable}
                options={applyRecaptchaTo}
                onChange={handleChange}
                desc2={__('Choose the form on which you need to apply hCAPTCHA.', 'loginpress-pro')}
              />
            </>
          )}

          {/* Cloudflare Turnstile Settings */}
          {settings.captchas_type === 'type_cloudflare' && (
            <>
              {/* Cloudflare Keys */}
              <div className={`lp-turnstile-validation ${settings.site_key_cf && settings.secret_key_cf && (cfValidated && !triggerValidation ? 'lp-validated' : 'lp-not-validated')}`}>
              <SettingField
                type="text"
                name="site_key_cf"
                label={__('Site Key', 'loginpress-pro')}
                value={settings.site_key_cf}
                onChange={handleChange}
                desc={
                  <>
                    {__('Get ', 'loginpress-pro')}
                    <a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank" rel="noopener noreferrer">{__('turnstile', 'loginpress-pro')}</a>
                    {__(' Site Key.', 'loginpress-pro')}
                  </>
                }
                desc4={__('Make sure you are adding right site key for this domain.', 'loginpress-pro')}
              />
              </div>
              <div className={`lp-turnstile-validation ${settings.site_key_cf && settings.secret_key_cf && (cfValidated && !triggerValidation ? 'lp-validated' : 'lp-not-validated')}`}>
                <SettingField
                  type="text"
                  name="secret_key_cf"
                  label={__('Secret Key', 'loginpress-pro')}
                  value={settings.secret_key_cf}
                  onChange={handleChange}
                  desc={
                    <>
                      {__('Get ', 'loginpress-pro')}
                      <a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank" rel="noopener noreferrer">{__('turnstile', 'loginpress-pro')}</a>
                      {__(' Secret Key.', 'loginpress-pro')}
                    </>
                  }
                  desc4={__('Make sure you are adding right secret key for this domain.', 'loginpress-pro')}
                />
              </div>

              {triggerValidation && (
                <div className='loginpress-setting-field'>
                  <label className="loginpress-setting-label"></label>
                <TurnstileWidget
                  siteKey={settings.site_key_cf}
                  secretKey={settings.secret_key_cf}
                  theme={settings.cf_theme}
                  show={!!settings.site_key_cf && !!settings.secret_key_cf}
                  onValidate={(success) => {
                    setCfValidated(success);
                    if (success) {
                      setTriggerValidation(false); // hide widget after success
                    }
                  }}
                />
                </div>
              )}
             
              {/* Cloudflare Theme */}
              <SettingField
                type="select"
                name="cf_theme"
                label={__('Choose Theme', 'loginpress-pro')}
                value={settings.cf_theme}
                options={themeOptions}
                onChange={handleChange}
                desc2={__('Select a theme for Turnstile', 'loginpress-pro')}
              />

              {/* Enable Cloudflare on */}
              <SettingField
                type="multicheck"
                name="captcha_enable_cf"
                label={__('Enable Turnstile on', 'loginpress-pro')}
                value={getMulticheckValue('captcha_enable_cf')}
                options={applyRecaptchaTo}
                onChange={handleChange}
                desc2={__('Choose the form on which you need to apply Cloudflare Turnstile.', 'loginpress-pro')}
              />
            </>
          )}
          </>
        )}
        <div className="loginpress-form-group">
          <button
            type="submit"
            className="button button-primary"
            disabled={saving || (settings.enable_captchas === 'off' && !hasUserInteracted)}
          >
            {saving ? __('Saving...', 'loginpress-pro') : __('Save Changes', 'loginpress-pro')}
          </button>
		  {message.text && (
        <div className={`message ${message.type}`}>
          <p>{message.text}</p>
		  <span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
        </div>
      )}
        </div>
      </form>
    
      
	</div>
    </>
  );
};

export default CaptchaSettings;