import React, { useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';

const TurnstileWidget = ({ siteKey, theme, secretKey, onValidate, show }) => {
  const widgetRef = useRef(null);
  const widgetIdRef = useRef(null); // Use ref instead of state for widgetId

  useEffect(() => {
    if (!window.turnstile || !siteKey || !secretKey || !show) return;

    // Clear previous widget if it exists
    if (widgetIdRef.current) {
      try {
        window.turnstile.remove(widgetIdRef.current);
      } catch (e) {
      }
      widgetRef.current.innerHTML = ''; // Clear previous widget container
    }

    // Render new widget
    widgetIdRef.current = window.turnstile.render(widgetRef.current, {
      sitekey: siteKey,
      theme: theme || 'light',
      callback: async (token) => {
        try {
          const res = await apiFetch({
            path: '/loginpress/v1/verify-turnstile',
            method: 'POST',
            data: {
              secret: secretKey,
              response: token,
            },
          });
          onValidate(res.success === true);
        } catch (err) {
          onValidate(false);
        }
      },
    });
  }, [siteKey, secretKey, theme, show]); // Depend on siteKey/secretKey so widget reloads

  return show ? <div ref={widgetRef} id="cf-turnstile-validate" /> : null;
};

export default TurnstileWidget;
