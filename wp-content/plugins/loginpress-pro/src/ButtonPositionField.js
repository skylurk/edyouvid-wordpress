import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import SettingField from './SettingField';
import apiFetch from '@wordpress/api-fetch';
import InfoBox from './InfoBox';

const ButtonPositionField = ({ name, value, onChange, options, label, desc }) => {
    const [hoveredOption, setHoveredOption] = useState(null);
    const [showImage, setShowImage] = useState(false);
    
    // Map of option values to image filenames
    const imageMap = {
      default: 'below-with-seprator',
      below: 'below',
      above: 'above',
      above_separator: 'above-with-separtor'
    };
  
    // Get the image path based on the active integration
    const imagePath = `${loginPressGlobal.integrationDirPath}/addons/social-login/assets/img/`;
  
    return (
      <div className="loginpress-setting-field">
        <label>{label}</label>
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