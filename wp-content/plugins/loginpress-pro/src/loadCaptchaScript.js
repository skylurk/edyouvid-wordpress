let lp_currentCaptchaScript = null;

// Dynamically load a new CAPTCHA script and remove previous
export function loadCaptchaScript(url) {
  return new Promise((resolve, reject) => {
    if (lp_currentCaptchaScript) {
      document.head.removeChild(lp_currentCaptchaScript);
      lp_currentCaptchaScript = null;
    }

    const script = document.createElement('script');
    script.src = url;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load CAPTCHA: ${url}`));

    document.head.appendChild(script);
    lp_currentCaptchaScript = script;
  });
}
