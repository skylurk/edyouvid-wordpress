// loginpress-pro/src/index.js

import CaptchaSettings from './CaptchaSettings';
import AutoLoginTab from './AutologinSettings';
import SocialLoginSettings from './SocialLoginSettings';
import HideLoginSettings from './HideLoginSettings';
import IntegrationSettings from './IntegrationSettings';
import LoginRedirects from './LoginRedirects';
import LimitLoginSettings from './LimitLoginSettings';
import LicenseManager from './LicenseManager';
import InfoBox from './InfoBox';
import React from 'react'; // Import React as it's used in JSX components
import './app-pro.css';

// This runs when the pro script is loaded
if (window.loginpressRegistry) {
  const { registerProComponent } = window.loginpressRegistry;
  
  registerProComponent('loginpress_captcha_settings', CaptchaSettings, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Captcha feature requires newer version', 'loginpress-pro')}</div>
  });
  
  registerProComponent('loginpress_autologin', AutoLoginTab, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Auto Login feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_login_redirects', LoginRedirects, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Login Redirects feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_limit_login_attempts', LimitLoginSettings, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Limit Login feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_hidelogin', HideLoginSettings, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Hide Login feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_social_logins', SocialLoginSettings, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Social Login feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_integration_settings', IntegrationSettings, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Integrations feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_pro_license', LicenseManager, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('License feature requires newer version', 'loginpress-pro')}</div>
  });

  registerProComponent('loginpress_pro_infobox', InfoBox, {
    requiredVersion: '6.0.0',
    fallback: () => <div class="test">{__('Info Box feature requires newer version', 'loginpress-pro')}</div>
  });


  // Register each pro component with its corresponding tab ID
  // registerProComponent('loginpress_captcha_settings', CaptchaSettings);
  // registerProComponent('loginpress_autologin', AutoLoginTab );
  // registerProComponent('loginpress_login_redirects', LoginRedirects );
  // registerProComponent('loginpress_limit_login_attempts', LimitLoginSettings );
  // registerProComponent('loginpress_hidelogin',  HideLoginSettings );
  // registerProComponent('loginpress_social_logins', SocialLoginSettings );
  // registerProComponent('loginpress_integration_settings',IntegrationSettings);
  // registerProComponent('loginpress_pro_license', LicenseManager );
  // registerProComponent('loginpress_pro_infobox', InfoBox );

} else {
  // This else block should now almost never be hit, unless the free plugin is inactive/broken.
}


