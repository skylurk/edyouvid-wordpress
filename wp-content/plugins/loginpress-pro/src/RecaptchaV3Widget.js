import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { loadCaptchaScript } from './loadCaptchaScript';

const RecaptchaV3Widget = ({ siteKey, secretKey, onValidate, show }) => {
  const [error, setError] = useState(false);

  useEffect(() => {
    if (!siteKey || !secretKey || !show) return;

    // Reset error state
    setError(false);

    // Initialize API fetch root if available
    if (typeof apiFetch?.createRootURLMiddleware === 'function') {
      apiFetch.use(apiFetch.createRootURLMiddleware('/wp-json/'));
    }

    const validateV3Keys = async () => {
      try {
        await loadCaptchaScript(`${loginpressLicense.recaptchaUrl}?render=${siteKey}`);

        if (!window.grecaptcha) {
          setError(true);
          onValidate(false);
          return;
        }

        window.grecaptcha.ready(async () => {
          try {
            const token = await window.grecaptcha.execute(siteKey, { action: 'validate' });

            const res = await apiFetch({
              path: '/loginpress/v1/verify-recaptcha-v3',
              method: 'POST',
              data: { secret: secretKey, response: token },
            });

            if (res?.success) {
              onValidate(true);
            } else {
              setError(true);
              onValidate(false);
            }
          } catch (error) {
            setError(true);
            onValidate(false);
          }
        });
      } catch (error) {
        setError(true);
        onValidate(false);
      }
    };

    validateV3Keys();
  }, [siteKey, secretKey, show]);

  // Hide the function/UI if there's an error
  if (!show || error) {
    return null;
  }

  return (
    <div className="recaptcha-v3-validation-message">
      <p>{__('Validating reCAPTCHA V3 keys', 'loginpress-pro')}<span class="wp_lg_validation_loader"></span></p>
    </div>
  );
};

export default RecaptchaV3Widget;
