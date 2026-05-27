import React, { useState, useEffect, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import TagInput from './TagInput';
// Generates a safe unique ID for fields
const generateFieldId = (name, suffix = '') =>
    `loginpress-setting-${name}${suffix ? `-${suffix}` : ''}`;

// Custom Select Component
const CustomSelect = ({ value, options, onChange, name, inputId, ...rest }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState('');
    const selectRef = useRef(null);

    useEffect(() => {
        const selectedOption = options.find(opt => opt.value === value);
        setSelectedLabel(selectedOption?.label || options[0]?.label || '');
    }, [value, options]);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (selectRef.current && !selectRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleOptionClick = (optionValue) => {
        onChange(name, optionValue);
        setIsOpen(false);
    };

    const toggleDropdown = () => {
        setIsOpen(!isOpen);
    };

    return (
        <div className="select regular" ref={selectRef}>
            <select
                id={inputId}
                name={name}
                className="regular s-hidden"
                value={value || ''}
                onChange={(e) => onChange(name, e.target.value)}
                {...rest}
            >
                {options.map(opt => (
                    <option key={opt.value} value={opt.value}>
                        {opt.label}
                    </option>
                ))}
            </select>
            <div className="styledSelect" onClick={toggleDropdown}>
                <span className="text-ellipses valueAdded">
                    {selectedLabel}
                </span>
            </div>
            <ul className="options" style={{ display: isOpen ? 'block' : 'none' }}>
                {options.map(opt => (
                    <li 
                        key={opt.value} 
                        rel={opt.value} 
                        className={value === opt.value ? 'active' : ''}
                        onClick={() => handleOptionClick(opt.value)}
                    >
                        {opt.label}
                    </li>
                ))}
            </ul>
        </div>
    );
};

const SettingField = ({
    ignoreValue = false,
    type,
    label,
    name,
    value,
    options = [],
    onChange,
    ...rest
}) => {
    const inputId = generateFieldId(name);

    const handleInputChange = (e) => {
        const val =
            type === 'checkbox' ? e.target.checked :
            type === 'number' ? parseInt(e.target.value, 10) || 0 :
            e.target.value;
        onChange(name, val);
    };

    const handleMultiCheckChange = (optionValue, checked) => {
        let currentValues;
        if (Array.isArray(value)) {
            currentValues = [...value];
        } else if (typeof value === 'object' && value !== null) {
            currentValues = Object.keys(value);
        } else {
            currentValues = [];
        }

        const updated = checked
            ? [...currentValues, optionValue]
            : currentValues.filter(v => v !== optionValue);

        onChange(name, updated);
    };

    const renderInput = () => {
        switch (type) {
            case 'checkbox':
                return (
                    <span className="loginpress-checkbox">
                        <input
                            id={inputId}
                            name={name}
                            type="checkbox"
                            checked={!!value}
                            onChange={handleInputChange}
                            {...rest}
                        />
                    </span>
                );

            case 'multicheck':
                let currentValues;
                if (Array.isArray(value)) {
                    currentValues = value;
                } else if (typeof value === 'object' && value !== null) {
                    currentValues = Object.keys(value);
                } else {
                    currentValues = [];
                }

                return (
                    <div className="loginpress-multicheck-options">
                        {options.map(opt => {
                            const optId = generateFieldId(name, opt.value);
                            return (
                                    <label key={opt.value} htmlFor={optId}>
									<span className="loginpress-checkbox">
                                    <input
                                        id={optId}
                                        name={`${name}[]`}
                                        type="checkbox"
                                        checked={currentValues.includes(opt.value)}
                                        onChange={(e) => handleMultiCheckChange(opt.value, e.target.checked)}
                                        {...rest}
                                    />
									</span>
                                    {opt.label}
                                </label>
                            );
                        })}
                    </div>
                );

            case 'radio':
                return (
                    <div className="loginpress-radio-options">
                        {options.map(opt => {
                            const optId = generateFieldId(name, opt.value);
                            return (
                                <div key={opt.value}>
                                    <input
                                        id={optId}
                                        name={name}
                                        type="radio"
                                        value={opt.value}
                                        checked={value === opt.value}
                                        onChange={handleInputChange}
                                    />
                                    <label htmlFor={optId}>{opt.label}</label>
                                </div>
                            );
                        })}
                    </div>
                );

            case 'radio-image':
                return (
                    <div className="loginpress-form-field">
                        <div className="loginpress-multicheck-container">
                            {options.map(option => {
                                const optId = generateFieldId(name, option.value);
                                return (
									<div className='loginpress-multicheck-item'>
                                    <label
                                        key={option.value}
                                        htmlFor={optId}
                                        className="loginpress-radio-option-container"
                                    >
                                        <div className={`loginpress-radio-option ${value === option.value ? 'active' : ''}`}>
                                        <input
                                            id={optId}
                                            name={name}
                                            type="radio"
                                            value={option.value}
                                            checked={value === option.value}
                                            onChange={() => onChange(name, option.value)}
                                        />
                                            {option.label}
                                        </div>
                                    </label>
									</div>
                                );
                            })}
                        </div>
                    </div>
                );

            case 'textarea':
                return (
                    <textarea
                        id={inputId}
                        name={name}
                        className="loginpress-textarea"
                        value={value || ''}
                        onChange={handleInputChange}
                        {...rest}
                    />
                );

            case 'select':
                return (
                    <CustomSelect
                        value={value}
                        options={options}
                        onChange={onChange}
                        name={name}
                        inputId={inputId}
                        {...rest}
                    />
                );
                case 'tags':
                    return (
                        <div className="lp-input-wrapper">
                            <TagInput
                                name={name}
                                value={value}
                                ignoreValue={ignoreValue}
                                onChange={onChange}
                                placeholder={rest.placeholder}
                                tagsSeparator={rest.tagsSeparator || ', '}
                                tagType={rest.tagType || 'text'}
                                maxTags={rest.maxTags}
                                disabled={rest.disabled}
                            />
                        </div>
                    );
            default:
                return (
                    <div className='lp-input-wrapper'>
                        <input
                            id={inputId}
                            name={name}
                            type={type}
                            className="loginpress-input"
                            value={value || ''}
                            onChange={handleInputChange}
                            {...rest}
                        />
                    </div>
                );
        }
    };

    return (
        <div className={`loginpress-setting-field loginpress-setting-field-${type}${rest.disabled ? ' loginpress-setting-field-disabled' : ''}`} data-label={label}>
            <label className="loginpress-setting-label" htmlFor={inputId}>
                {label}
            </label>
            <div className="loginpress-setting-input" style={{ flex: 1 }}>
                {renderInput()}
                {rest.desc && (
                    <label className="loginpress-label-des desc2" htmlFor={inputId}>
                        {rest.desc}
                    </label>
                )}
                {rest.desc2 && (
                    <p className="loginpress-description" style={{ marginTop: rest.desc ? '4px' : undefined }}>
                        {rest.desc2}
                    </p>
                )}
                {rest.desc3 && (
                    <p
                        className="loginpress-description"
                        style={{
                            marginTop:
                                rest.desc2
                                    ? '2px'
                                    : rest.desc
                                    ? '2px'
                                    : undefined,
                        }}
                        dangerouslySetInnerHTML={{ __html: rest.desc3 }}
                    />
                )}
                {rest.desc4 && (
                    <p
                        className="loginpress-description"
                        style={{
                            marginTop:
                                rest.desc3
                                    ? '2px'
                                    : rest.desc2
                                    ? '2px'
                                    : rest.desc
                                    ? '2px'
                                    : undefined,
                        }}
                    >
                        {rest.desc4}
                    </p>
                )}
				{rest.descdiv && (
					<div className="loginpress-description">
						{rest.descdiv}
					</div>
				)}
				{rest.hr &&(
					<hr className='loginpress-hr'/>
				)}
            </div>
        </div>
    );
};

export default SettingField;
