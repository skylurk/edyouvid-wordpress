import React, { useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { loadCaptchaScript } from './loadCaptchaScript';

const HcaptchaWidget = ({ siteKey, secretKey, theme = 'light', onValidate, show }) => {
  const widgetRef = useRef(null);

  useEffect(() => {
    if (!siteKey || !secretKey || !show) return;

    if (widgetRef.current) {
      widgetRef.current.innerHTML = '';
    }

    loadCaptchaScript(loginpressLicense.hcaptchaUrl)
      .then(() => {
        if (!window.hcaptcha || !widgetRef.current) return;

        window.hcaptcha.render(widgetRef.current, {
          sitekey: siteKey,
          theme,
          callback: async (token) => {
            try {
              const res = await apiFetch({
                path: '/loginpress/v1/verify-hcaptcha',
                method: 'POST',
                data: { secret: secretKey, response: token },
              });
              onValidate(res.success === true);
            } catch (e) {
              onValidate(false);
            }
          },
        });
      })
		.catch(() => {
        onValidate(false);
      });

    return () => {
      if (widgetRef.current) widgetRef.current.innerHTML = '';
    };
  }, [siteKey, secretKey, show]);

  return show ? <div ref={widgetRef} id="hcaptcha-widget" /> : null;
};

export default HcaptchaWidget;
