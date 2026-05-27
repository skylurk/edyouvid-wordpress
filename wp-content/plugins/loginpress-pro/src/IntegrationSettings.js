import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import SettingField from './SettingField';
import apiFetch from '@wordpress/api-fetch';
import InfoBox from './InfoBox';
import './IntegrationSettings.css';

const IntegrationSettings = () => {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [activeIntegration, setActiveIntegration] = useState(null);
    const [message, setMessage] = useState(null);
	const [hasChanges, setHasChanges] = useState(false);
    const [initialSettings, setInitialSettings] = useState({});

    // Fetch settings on component mount
    const socialLogin = Array.isArray(loginpressProData?.activeAddons) &&
                    loginpressProData.activeAddons.includes("social-login");
    var captchaSettings = true;
    const storedValue = localStorage.getItem('loginpress_captcha_enabled');
    captchaSettings = storedValue === 'true'; // convert to boolean

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const [integrationResponse, captchaResponse] = await Promise.all([
                    apiFetch({
                        path: '/loginpress/v1/integration-settings',
                        method: 'GET'
                    }),
                ]);
                setSettings(integrationResponse);
                setInitialSettings(integrationResponse);
            } catch (error) {
            } finally {
                setLoading(false);
            }
        };


        fetchSettings();
    }, []);

    // Track changes to settings
    useEffect(() => {
        if (Object.keys(initialSettings).length > 0) {
            setHasChanges(JSON.stringify(settings) !== JSON.stringify(initialSettings));
        }
    }, [settings, initialSettings]);

    // Handle setting changes (local state only)
    const handleChange = (name, value) => {
        // Special handling for multicheck fields
        if (name.startsWith('enable_captcha_')) {
            if (Array.isArray(value)) {
                // Convert array to associative object format
                const newValue = {};
                value.forEach(key => {
                    newValue[key] = key;
                });
                setSettings(prev => ({ ...prev, [name]: newValue }));
            } else {
                setSettings(prev => ({ ...prev, [name]: value }));
            }
        } else {
            setSettings(prev => ({ ...prev, [name]: value }));
        }
    };

    // Save settings to API
    const saveSettings = async () => {
        try {
            setLoading(true);
            const response = await apiFetch({
                path: '/loginpress/v1/integration-settings',
                method: 'POST',
                data: { settings }
            });
            
            setMessage({
                text: response.message || __('Settings saved successfully.', 'loginpress-pro'),
                type: 'success'
            });
            
            // Update initial settings to reflect the saved state
            setInitialSettings(settings);
            setHasChanges(false);
            
        } catch (error) {
            setMessage({
                text: __('Failed to save settings.', 'loginpress-pro'),
                type: 'error'
            });
        } finally {
            setLoading(false);
        }
    };

    const renderSaveButton = () => {
        if (!activeIntegration) return null;
        
        return (
            <div className="loginpress-form-group">
                <button
                    className="button button-primary"
                    type='submit'
                    onClick={saveSettings}
                    // disabled={!hasChanges || loading}
                >
                    {loading ? __('Saving...', 'loginpress-pro') : __('Save Changes', 'loginpress-pro')}
                </button>
				{renderMessage()}
            </div>
        );
    };

    const integrations = [
        { 
            name: 'WooCommerce', 
            class: 'woocommerce', 
            description: loginpress_integration_data.plugins.woocommerce.description, 
            logo: 'woocommerce.svg',
            status: loginpress_integration_data.plugins.woocommerce.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-woocommerce-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=woocommerce`
        },
        { 
            name: 'Easy Digital Downloads', 
            class: 'edd', 
            description: loginpress_integration_data.plugins.edd.description, 
            logo: 'edd.svg',
            status: loginpress_integration_data.plugins.edd.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-easy-digital-downloads-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=edd`
        },
        { 
            name: 'BuddyPress', 
            class: 'buddypress', 
            description: loginpress_integration_data.plugins.buddypress.description, 
            logo: 'buddypress.svg',
            status: loginpress_integration_data.plugins.buddypress.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-buddypress-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=buddypress`
        },
        { 
            name: 'BuddyBoss', 
            class: 'buddyboss', 
            description: loginpress_integration_data.plugins.buddyboss.description, 
            logo: 'buddyboss.svg',
            status: loginpress_integration_data.plugins.buddyboss.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-buddyboss-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=buddyboss`
        },
        { 
            name: 'LifterLMS', 
            class: 'lifterlms', 
            description: loginpress_integration_data.plugins.lifterlms.description, 
            logo: 'lifterlms.svg',
            status: loginpress_integration_data.plugins.lifterlms.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-lifterlms-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=lifterlms`
        },
        { 
            name: 'LearnDash', 
            class: 'learndash', 
            description: loginpress_integration_data.plugins.learndash.description, 
            logo: 'learndash.svg',
            status: loginpress_integration_data.plugins.learndash.status,
            configuration_guide: `https://loginpress.pro/doc/loginpress-learndash-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=learndash`
        },
    ];

    const buttonPositionOptions = [
        { value: 'default', label: __('Default (Show below the separator)', 'loginpress-pro') },
        { value: 'below', label: __('Below (Show below the fields)', 'loginpress-pro') },
        { value: 'above', label: __('Above (Show above the fields)', 'loginpress-pro') },
        { value: 'above_separator', label: __('Above with Separator (Show above the separator)', 'loginpress-pro') },
    ];

    const quizPositionOptions = [
        { value: 'below', label: __('Below (Show below the fields)', 'loginpress-pro') },
        { value: 'above', label: __('Above (Show above the fields)', 'loginpress-pro') },
    ];

    const handleIntegrationClick = (integration) => {
        if (integration.status === 'active') {
            setActiveIntegration(integration.class);
            updateUrlParam(integration.class);
        }
    };

    const handleBackClick = () => {
        setActiveIntegration(null);
        updateUrlParam(null);
    };

    const updateUrlParam = (integrationClass) => {
        const url = new URL(window.location.href);
        if (integrationClass) {
            url.searchParams.set('integration', integrationClass);
        } else {
            url.searchParams.delete('integration');
        }
        window.history.pushState({}, '', url);
    };

    useEffect(() => {
        // Check URL for integration parameter on initial load
        const urlParams = new URLSearchParams(window.location.search);
        const integrationParam = urlParams.get('integration');
        if (integrationParam) {
            const integration = integrations.find(i => i.class === integrationParam);
            if (integration && integration.status === 'active') {
                setActiveIntegration(integrationParam);
            }
        }
    }, []);

    const ButtonPositionField = ({ name, value, onChange, options, label, desc }) => {
        const [hoveredOption, setHoveredOption] = useState(null);
        const [showImage, setShowImage] = useState(false);
         // Map of option values to image filenames
         let imageMap = {
            default: 'below-with-seprator',
            below: 'below',
            above: 'above',
            above_separator: 'above-with-separtor'
          };
        if (name === 'social_position_ld_qf'){
             imageMap = {
                below: 'below',
                above: 'above',
              };
        }
      
        // Get the image path based on the active integration
        const imagePath = `${loginPressGlobal.socialDirPath}/assets/img/`;
      
        return (
          <div className="loginpress-setting-field">
            <label className='loginpress-setting-label'>{label}</label>
            <div className="button-position-options">
              {options.map((option) => (
                <div 
                  key={option.value}
                  className={`button-position-option ${value === option.value ? 'active' : ''}`}
                  onMouseEnter={() => {
                    setHoveredOption(option.value);
                    setShowImage(true);
                  }}
                  onMouseLeave={() => {
                    setHoveredOption(null);
                    setShowImage(false);
                  }}
                >
                  <label>
                    <input
                      type="radio"
                      name={name}
                      value={option.value}
                      checked={value === option.value}
                      onChange={() => onChange(name, option.value)}
                      disabled={!socialLogin}
                    />
                    {option.label}
                  </label>
                </div>
              ))}
            </div>
            
            {/* Image preview container */}
            <div className="button-position-preview">
              {showImage && hoveredOption && (
                <img 
                  src={`${imagePath}${imageMap[hoveredOption]}.svg`} 
                  alt={`${hoveredOption} position preview`}
                  className="position-preview-image"
                />
              )}
              {!showImage && value && (
                <img 
                  src={`${imagePath}${imageMap[value]}.svg`} 
                  alt={`${value} position preview`}
                  className="position-preview-image"
                />
              )}
            </div>
            
            {desc && <p className="description">{desc}</p>}
          </div>
        );
      };
    const renderWooCommerceSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_woo"
                value={settings.enable_captcha_woo || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'woocommerce_login_form', label: __('WooCommerce Login Form', 'loginpress-pro') },
                    { value: 'woocommerce_register_form', label: __('WooCommerce Register Form', 'loginpress-pro') },
                    { value: 'woocommerce_checkout_form', label: __('WooCommerce Checkout Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro')}
				hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Login Form', 'loginpress-pro')}
                name="enable_social_woo_lf"
                value={settings.enable_social_woo_lf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on WooCommerce login form.', 'loginpress-pro')}
            />

            {settings.enable_social_woo_lf === 'on' && (
               <ButtonPositionField
                    label={__('Button Position on Login Form', 'loginpress-pro')}
                    name="social_position_woo_lf"
                    value={settings.social_position_woo_lf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                    // desc={__('Select where to display social login buttons', 'loginpress-pro')}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Register Form', 'loginpress-pro')}
                name="enable_social_woo_rf"
                value={settings.enable_social_woo_rf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on WooCommerce register form.', 'loginpress-pro')}
            />

            {settings.enable_social_woo_rf === 'on' && (
               <ButtonPositionField
               label={__('Button Position on Register Form', 'loginpress-pro')}
               name="social_position_woo_rf"
               value={settings.social_position_woo_rf || 'default'}
               onChange={handleChange}
               options={buttonPositionOptions}
            //    desc={__('Select where to display social login buttons', 'loginpress-pro')}
             />
            )}

            <SettingField
                type="checkbox"
                label={__('Checkout Form', 'loginpress-pro')}
                name="enable_social_woo_co"
                value={settings.enable_social_woo_co === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on WooCommerce checkout form.', 'loginpress-pro')}
            />

            {settings.enable_social_woo_co === 'on' && (
                <ButtonPositionField
                label={__('Button Position on Checkout Form', 'loginpress-pro')}
                name="social_position_woo_co"
                value={settings.social_position_woo_co || 'default'}
                onChange={handleChange}
                options={buttonPositionOptions}
                // desc={__('Select where to display social login buttons', 'loginpress-pro')}
              />
            )}
        </>
    );

    const renderLearnDashSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_ld"
                value={settings.enable_captcha_ld || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'login_learndash', label: __('Login Form', 'loginpress-pro') },
                    { value: 'register_learndash', label: __('Register Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro')}
				hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Login Form', 'loginpress-pro')}
                name="enable_social_ld_lf"
                value={settings.enable_social_ld_lf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LearnDash login form.', 'loginpress-pro')}
            />

            {settings.enable_social_ld_lf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Login Form', 'loginpress-pro')}
                    name="social_position_ld_lf"
                    value={settings.social_position_ld_lf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                    // desc={loginpress_integration_data.loginpress_social_position_image}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Register Form', 'loginpress-pro')}
                name="enable_social_ld_rf"
                value={settings.enable_social_ld_rf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LearnDash register form.', 'loginpress-pro')}
            />

            {settings.enable_social_ld_rf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Register Form', 'loginpress-pro')}
                    name="social_position_ld_rf"
                    value={settings.social_position_ld_rf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Quiz', 'loginpress-pro')}
                name="enable_social_ld_qf"
                value={settings.enable_social_ld_qf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LearnDash Quiz.', 'loginpress-pro')}
            />

            {settings.enable_social_ld_qf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Quiz', 'loginpress-pro')}
                    name="social_position_ld_qf"
                    value={settings.social_position_ld_qf || 'below'}
                    onChange={handleChange}
                    options={quizPositionOptions}
                />
            )}
        </>
    );

    const renderLifterLMSSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_llms"
                value={settings.enable_captcha_llms || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'lifter_login_form', label: __('LifterLMS Login Form', 'loginpress-pro') },
                    { value: 'lifter_register_form', label: __('LifterLMS Register Form', 'loginpress-pro') },
                    { value: 'lifter_lostpassword_form', label: __('LifterLMS Lost Password Form', 'loginpress-pro') },
                    { value: 'lifter_purchase_form', label: __('LifterLMS Purchase Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro')}
				hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Login Form', 'loginpress-pro')}
                name="enable_social_llms_lf"
                value={settings.enable_social_llms_lf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LifterLMS login form.', 'loginpress-pro')}
            />

            {settings.enable_social_llms_lf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Login Form', 'loginpress-pro')}
                    name="social_position_llms_lf"
                    value={settings.social_position_llms_lf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Register Form', 'loginpress-pro')}
                name="enable_social_llms_rf"
                value={settings.enable_social_llms_rf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LifterLMS register form.', 'loginpress-pro')}
            />

            {settings.enable_social_llms_rf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Register Form', 'loginpress-pro')}
                    name="social_position_llms_rf"
                    value={settings.social_position_llms_rf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Purchase Form', 'loginpress-pro')}
                name="enable_social_llms_co"
                value={settings.enable_social_llms_co === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on LifterLMS purchase form.', 'loginpress-pro')}
            />

            {settings.enable_social_llms_co === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Purchase Form', 'loginpress-pro')}
                    name="social_position_llms_co"
                    value={settings.social_position_llms_co || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}
        </>
    );

    const renderBuddyPressSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_bp"
                value={settings.enable_captcha_bp || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'register_bp_block', label: __('Register Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro')}
				hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Enable Social Login on', 'loginpress-pro')}
                name="enable_social_login_links_bp"
                value={settings.enable_social_login_links_bp === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Buddypress Register Form', 'loginpress-pro')}
            />

            {settings.enable_social_login_links_bp === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Register Form', 'loginpress-pro')}
                    name="social_position_bp"
                    value={settings.social_position_bp || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}
        </>
    );

    const renderBuddyBossSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_bb"
                value={settings.enable_captcha_bb || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'register_bb_block', label: __('Register Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro')}
				hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Enable Social Login on', 'loginpress-pro')}
                name="enable_social_login_links_bb"
                value={settings.enable_social_login_links_bb === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Buddyboss Register Form', 'loginpress-pro')}
            />

            {settings.enable_social_login_links_bb === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Register Form', 'loginpress-pro')}
                    name="social_position_bb"
                    value={settings.social_position_bb || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}
        </>
    );

    const renderEDDSettings = () => (
        <>
            {!captchaSettings && (
                <div className="loginpress-captcha-warning">
                    {__('Please enable Captcha first.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="multicheck"
                label={__('Enable Captcha on', 'loginpress-pro')}
                name="enable_captcha_edd"
                value={settings.enable_captcha_edd || []}
                onChange={handleChange}
                disabled={!captchaSettings}
                options={[
                    { value: 'login_edd_block', label: __('Login Form', 'loginpress-pro') },
                    { value: 'register_edd_block', label: __('Register Form', 'loginpress-pro') },
                    { value: 'checkout_edd_block', label: __('Checkout Form', 'loginpress-pro') },
                ]}
                desc2={__('Choose the form on which you need to apply the selected captcha.', 'loginpress-pro') }
                hr={true}
            />
            {!socialLogin && (
                <div className="loginpress-captcha-warning">
                    {__('Activate Social Login addon to use following settings.', 'loginpress-pro')}
                </div>
            )}
            <SettingField
                type="checkbox"
                label={__('Login Form', 'loginpress-pro')}
                name="enable_social_edd_lf"
                value={settings.enable_social_edd_lf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on Edd login form.', 'loginpress-pro')}
            />

            {settings.enable_social_edd_lf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Login Form', 'loginpress-pro')}
                    name="social_position_edd_lf"
                    value={settings.social_position_edd_lf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Register Form', 'loginpress-pro')}
                name="enable_social_edd_rf"
                value={settings.enable_social_edd_rf === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on Edd register form.', 'loginpress-pro')}
            />

            {settings.enable_social_edd_rf === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Register Form', 'loginpress-pro')}
                    name="social_position_edd_rf"
                    value={settings.social_position_edd_rf || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}

            <SettingField
                type="checkbox"
                label={__('Checkout Form', 'loginpress-pro')}
                name="enable_social_edd_co"
                value={settings.enable_social_edd_co === 'on'}
                onChange={(name, value) => handleChange(name, value ? 'on' : 'off')}
                disabled={!socialLogin}
                desc={__('Enable to add Social Login on Edd checkout form.', 'loginpress-pro')}
            />

            {settings.enable_social_edd_co === 'on' && (
                <ButtonPositionField
                    label={__('Button Position on Checkout Form', 'loginpress-pro')}
                    name="social_position_edd_co"
                    value={settings.social_position_edd_co || 'default'}
                    onChange={handleChange}
                    options={buttonPositionOptions}
                />
            )}
        </>
    );

    const renderIntegrationSettings = () => {
        switch (activeIntegration) {
            case 'woocommerce':
                return renderWooCommerceSettings();
            case 'edd':
                return renderEDDSettings();
            case 'buddypress':
                return renderBuddyPressSettings();
            case 'buddyboss':
                return renderBuddyBossSettings();
            case 'lifterlms':
                return renderLifterLMSSettings();
            case 'learndash':
                return renderLearnDashSettings();
            default:
                return null;
        }
    };

    const renderMessage = () => {
		if (!message) return null;
	
		setTimeout(() => {
			setMessage(null); // Use setter instead of direct assignment
		}, 3000);
	
		return (
			message.text && (
				<div className={`message ${message.type}`}>
					<p>{message.text}</p>
					<span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
				</div>
			)
		);
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
      heading={__('Integrations', 'loginpress-pro')}
      description={__('LoginPress integrates with the most popular WordPress plugins to enhance your login experience. Our Social Login, CAPTCHA and Limit Login Attempts features among others are easily integrated into these platforms, helping you streamline user access and enhance security.', 'loginpress-pro')}
    />
        <div id="loginpress_integration_settings" className="loginpress-hidelogin-container">
            {activeIntegration && (
                    <button 
                        onClick={handleBackClick}
                        className="go-back-button"
                    >
                        <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7.84626 0.250834C7.50119 -0.0836112 6.9419 -0.0836112 6.59682 0.250834L0.260737 6.39178C0.239204 6.41265 0.218776 6.43459 0.199452 6.4576C0.193379 6.46455 0.18841 6.47204 0.182336 6.47954C0.170742 6.49452 0.159147 6.50897 0.148105 6.52449C0.140375 6.53572 0.13375 6.5475 0.126573 6.55873C0.118843 6.57104 0.111113 6.58335 0.103936 6.59619C0.0967581 6.60903 0.0906849 6.62188 0.0846117 6.63525C0.0785384 6.64756 0.0724651 6.65933 0.066944 6.67164C0.0608707 6.68555 0.0564538 6.69947 0.0514848 6.71338C0.0470678 6.72569 0.0420989 6.738 0.0382341 6.7503C0.033265 6.76582 0.0299523 6.78134 0.0260875 6.79632C0.0233269 6.80809 0.020014 6.81933 0.0172534 6.83111C0.0128365 6.85144 0.010076 6.87177 0.00786752 6.89211C0.00676329 6.8996 0.00565919 6.90656 0.00455496 6.91405C-0.00151832 6.9713 -0.00151832 7.0291 0.00455496 7.08635C0.00510708 7.08956 0.00565912 7.09277 0.00621124 7.09598C0.00897182 7.1206 0.0128366 7.14522 0.0178056 7.1693C0.019462 7.17625 0.0216704 7.18321 0.0233267 7.19017C0.0282958 7.2105 0.0332649 7.23083 0.0393381 7.25063C0.0415466 7.25759 0.0443071 7.26455 0.0470677 7.27204C0.0536931 7.2913 0.0608707 7.3111 0.0691525 7.33036C0.071913 7.33625 0.0746737 7.3416 0.0774343 7.34749C0.0868203 7.36729 0.0962062 7.38709 0.106696 7.40635C0.108905 7.4101 0.111665 7.41384 0.113874 7.41759C0.125468 7.43792 0.138167 7.45826 0.151418 7.47806C0.152522 7.47966 0.153626 7.48127 0.154731 7.48287C0.185097 7.52622 0.21988 7.56742 0.259081 7.60541L6.59793 13.749C6.77019 13.916 6.99656 14 7.22237 14C7.44819 14 7.67455 13.9165 7.84682 13.749C8.19189 13.4146 8.19189 12.8725 7.84682 12.5381L3.0158 7.85584H19.1166C19.6047 7.85584 20 7.4727 20 6.99967C20 6.52663 19.6047 6.14349 19.1166 6.14349H3.0158L7.84626 1.46126C8.19134 1.12735 8.19134 0.585279 7.84626 0.250834Z" fill="#2B3D54"/>
                        </svg>
                        {__('Back', 'loginpress-pro')}
                    </button>
                )}
            <div className="loginpress-integration-head-wrapper">
                
                {activeIntegration && (
                    <img 
                        src={`${loginpress_script.plugin_url}/loginpress/img/${activeIntegration}.svg`} 
                        alt={integrations.find(i => i.class === activeIntegration)?.name} 
                    />
                )}
            </div>

            <div id="loginpress-integration-management">
                {!activeIntegration ? (
                    <div id="integration-cards-container" className="loginpress-integration-container" style={{ display: 'flex', flexWrap: 'wrap' }}>
                        {integrations.map(integration => (
                            <div 
                                key={integration.class}
                                className="loginpress-integration-card" 
                                data-integration={integration.class}
                            >
                                <div className="loginpress-integration-head">
                                    <img src={`${loginpress_script.plugin_url}/loginpress/img/${integration.class}.svg`} alt={integration.name} />
                                </div>
                                <div className="loginpress-integration-body">
                                    <h3>{integration.name}</h3>
                                    <p>{integration.description}</p>
                                </div>
                                <div className="loginpress-integration-foot">
                                    {integration.status === 'active' ? (
                                        <button 
                                            className="loginpress-integration-button"
                                            onClick={() => handleIntegrationClick(integration)}
                                        >
                                            {__('Configure', 'loginpress-pro')}
                                        </button>
                                    ) : integration.status === 'learn-more' ? (
                                        <a 
                                            href={integration.configuration_guide}
                                            target="_blank" 
                                            rel="noopener noreferrer"
                                            className="loginpress-integration-button"
                                        >
                                            {__('Configuration Guide', 'loginpress-pro')}
                                            <svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.875 5.36621L13.875 9.36621L9.875 13.3662" stroke="currentColor" strokeWidth="1.2" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
                                                <path d="M3.875 9.36621L13.875 9.36621" stroke="currentColor" strokeWidth="1.2" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round"/>
                                            </svg>
                                        </a>
                                    ) : (
                                        <span className="message warning">
                                            {__('Activate', 'loginpress-pro')} {integration.name !== 'Easy Digital Downloads' ? integration.name : 'EDD'} {__('to proceed.', 'loginpress-pro')}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div id="integration-settings-container">
                        <div className="integration-settings-content">
                            {/* <h2 id="integration-name">
                                {integrations.find(i => i.class === activeIntegration)?.name}
                            </h2> */}
                            <div id="integration-specific-settings">
                                {renderIntegrationSettings()}
                            </div>
                        </div>
                        {activeIntegration && (
                            <div className="loginpress-provider-description" style={{display: 'block'}}>
                                <h4 className="loginpress-provider-logo">
                                    {__('Help Center', 'loginpress-pro')}
                                </h4>
                                <p>
                                    {__('Follow our step-by-step guide for', 'loginpress-pro')} <span className="loginpress-provider-name">
                                        {integrations.find(i => i.class === activeIntegration)?.name}
                                    </span> {__('Integration:', 'loginpress-pro')}<br />
                                    <a 
                                        href={`https://loginpress.pro/doc/loginpress-${activeIntegration=='edd'?'easy-digital-downloads':activeIntegration}-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=${activeIntegration}`} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                    >
                                       {__('How to integrate', 'loginpress-pro')} {integrations.find(i => i.class === activeIntegration)?.name} {__('with LoginPress Pro', 'loginpress-pro')}
                                    </a>
                                </p>
                            </div>
                        )}
                        {renderSaveButton()}
                    </div>
                )}
            </div>

            
        </div>
        </>
    );
};

export default IntegrationSettings;