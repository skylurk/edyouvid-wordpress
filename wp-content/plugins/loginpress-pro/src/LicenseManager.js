import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

const LicenseManager = () => {
    const [licenseData, setLicenseData] = useState({
        key: '',
        status: '',
        expiration: '',
        type: '',
        error: ''
    });
    const [isLoading, setIsLoading] = useState(true);
    const [isProcessing, setIsProcessing] = useState(false);
    const isLicenseActive = licenseData.status === 'valid';
    const isInputDisabled = isLicenseActive;
    const buttonText = isLicenseActive 
        ? __('Deactivate License', 'loginpress-pro') 
        : __('Activate License', 'loginpress-pro');
    const isButtonDisabled = isProcessing || 
        (!isLicenseActive && !licenseData.key.trim());
    useEffect(() => {
        const fetchLicenseData = async () => {
            try {
                const response = await apiFetch({
                    path: '/loginpress/v1/license',
                    method: 'GET'
                });

                if (response.success) {
                    if (response.status === 'deactivated') {
                        setLicenseData({
                            key: '',
                            status: 'deactivated',
                            expiration: '',
                            type: '',
                            error: ''
                        });
                    } else {
                        setLicenseData({
                            key: response.license_key || '',
                            status: response.status || 'invalid',
                            expiration: response.expiration || '',
                            type: response.type || '',
                            error: ''
                        });
                    }
                }
            } catch (error) {
                setLicenseData(prev => ({
                    ...prev,
                    error: __('Failed to fetch license data', 'loginpress-pro')
                }));
            } finally {
                setIsLoading(false);
            }
        };

        fetchLicenseData();
    }, []);

    const handleLicenseAction = async (action) => {
        setIsProcessing(true);

        try {
            const currentKey = document.getElementById('loginpress_pro_license_key')?.value || licenseData.key;
            const response = await apiFetch({
                path: '/loginpress/v1/license',
                method: 'POST',
                data: {
                    action,
                    license_key: currentKey
                }
            });

            if (response.success) {
                if (action === 'deactivate') {
                    setLicenseData({
                        key: '',
                        status: 'deactivated',
                        expiration: '',
                        type: '',
                        error: ''
                    });
                } else if (action === 'activate') {
                    setLicenseData({
                        key: response.license_key || currentKey,
                        status: 'valid',
                        expiration: response.expiration || '',
                        type: response.type || '',
                        error: ''
                    });
                }
            } else {
                setLicenseData(prev => ({
                    ...prev,
                    error: response.error || __('Unknown error occurred', 'loginpress-pro')
                }));
            }
        } catch (error) {
            setLicenseData(prev => ({
                ...prev,
                error: error.message || __('Failed to process license', 'loginpress-pro')
            }));
        } finally {
            setIsProcessing(false);
        }
    };

    return (
            <div id="loginpress-license-settings" className="group">
                <div className='loginpress-info-box'>
                    <h2>{__('License Manager', 'loginpress-pro')}</h2>
                </div>
                <div className="loginpress-settings loginpress-license-settings">
                    <div className="loginpress-setting-field loginpress-setting-field-text" data-label="License Key">
                        <label className="loginpress-setting-label" htmlFor="loginpress_pro_license_key">
                            {__('License Key', 'loginpress-pro')}
                        </label>
                        <div className="loginpress-setting-input" style={{flex: '1 1 0%'}}>
						{isLoading ? (
							<div className="loginpress-skeleton loginpress-input" style={{height: '38px', width: '100%', maxWidth: '600px'}}></div>
						):(
                            <input
                                id="loginpress_pro_license_key"
                                name="license_key"
                                required
                                placeholder={__('Enter your license key', 'loginpress-pro')}
                                type="text"
                                className="loginpress-input"
                                value={licenseData.key}
                                onChange={(e) =>
                                    setLicenseData(prev => ({
                                        ...prev,
                                        key: e.target.value,
                                        error: ''
                                    }))
                                }
                                disabled={isInputDisabled}
                            />
						)}
                            <div className="loginpress-description-2">
                                {__('Validating license key is mandatory to use automatic updates and plugin support.', 'loginpress-pro')}
                            </div>
                        </div>
                    </div>

                    <div className="loginpress-setting-field loginpress-setting-field-button" data-label={__("License Action", "loginpress-pro")}>
                        <label className="loginpress-setting-label"></label>
                        <div className="loginpress-setting-input" style={{flex: '1 1 0%'}}>
						{isLoading ? (
							<div className="loginpress-skeleton loginpress-button" style={{height: '58px', maxWidth: '180px', width: '100%'}}></div>
						):(
                            <button
                                type="button"
                                className={`button-${isLicenseActive ? 'secondary' : 'primary'}`}
                                onClick={() => handleLicenseAction(isLicenseActive ? 'deactivate' : 'activate')}
                                disabled={isButtonDisabled}
                            >
                                {isProcessing ? __('Processing...', 'loginpress-pro') : buttonText}
                            </button>
						)}
                        </div>
                    </div>

                    <div className="loginpress-setting-field loginpress-setting-field-info" data-label={__("License Status", "loginpress-pro")}>
                        <label className="loginpress-setting-label"></label>
                        <div className="loginpress-setting-input" style={{flex: '1 1 0%'}}>
                            <div className="loginpress-license-desc">
							{isLoading ? (
								<p>{__('Loading license information...', 'loginpress-pro')}</p>
							) : licenseData.error ? (
                                    <div className="loginpress-license-error">
                                        {licenseData.error}
                                    </div>
                                ) : licenseData.status === 'valid' ? (
                                    <>
                                        <p>
                                            {sprintf(
                                                __('Your (%s) license key is valid until ', 'loginpress-pro'),
                                                licenseData.type
                                            )}
                                            <strong>{licenseData.expiration}</strong>.
                                        </p>

                                        {licenseData.expiration !== 'lifetime' && (
                                            <p className="loginpress-license-renewal">
                                                {__('The license will automatically renew if you have an active subscription at ', 'loginpress-pro')}
                                                <a
                                                    href="https://wpbrigade.com/account/"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    WPBrigade.com
                                                </a>.
                                            </p>
                                        )}
                                    </>
                                ) : licenseData.status === 'deactivated' ? (
                                    <p>{__('Your license key is deactivated.', 'loginpress-pro')}</p>
                                ) : (
                                    <p>{__('No license key entered.', 'loginpress-pro')}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    );
};

export default LicenseManager;