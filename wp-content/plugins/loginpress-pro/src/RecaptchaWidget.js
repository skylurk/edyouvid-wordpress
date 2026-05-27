import React, { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { loadCaptchaScript } from './loadCaptchaScript';

const RecaptchaWidget = ({ siteKey, secretKey, theme = 'light', onValidate, show }) => {
  const [widgetId, setWidgetId] = useState(() => `recaptcha-${Math.random().toString(36).substring(2, 10)}`);

  useEffect(() => {
    if (!siteKey || !show) return;

    // Remove any existing reCAPTCHA elements from previous render
    const oldContainer = document.getElementById(widgetId);
    if (oldContainer) {
      oldContainer.remove();
    }

    // Create new container div with a new ID
    const newContainer = document.createElement('div');
    newContainer.id = widgetId;
    document.getElementById('recaptcha-placeholder')?.appendChild(newContainer);

    loadCaptchaScript(loginpressLicense.recaptchaUrl)
      .then(() => {
        if (!window.grecaptcha || !newContainer) return;

        window.grecaptcha.ready(() => {
          window.grecaptcha.render(newContainer, {
            sitekey: siteKey,
            theme,
            callback: async (token) => {
              try {
                const res = await apiFetch({
                  path: '/loginpress/v1/verify-recaptcha',
                  method: 'POST',
                  data: { secret: secretKey, response: token },
                });
                onValidate(res.success === true);
              } catch (e) {
                onValidate(false);
              }
            },
          });
        });
      })
		.catch(() => {
        onValidate(false);
      });

    return () => {
      const cleanup = document.getElementById(widgetId);
      if (cleanup) cleanup.remove();
    };
  }, [siteKey, secretKey, show, widgetId]);

  return show ? <div id="recaptcha-placeholder" /> : null;
};

export default RecaptchaWidget;
