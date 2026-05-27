import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

const InfoBox = ({ heading, description, videoUrl, className }) => {
  const [showVideo, setShowVideo] = useState(false);
  const [currentVideoUrl, setCurrentVideoUrl] = useState('');

  let finalDescription = description;
  let finalVideoUrl = videoUrl;

  // Override values if heading is "Settings"
  if (heading === 'Settings') {
    finalDescription = (
      <>
        {__('Everything else is customizable through', 'loginpress-pro')}{' '}
        <a
          href={`${loginpress_pro_local.siteUrl}/wp-admin/admin.php?page=loginpress`}
        >
          {__('WordPress Customizer', 'loginpress-pro')}
        </a>
        .
      </>
    );
    finalVideoUrl = 'https://www.youtube.com/watch?v=GMAwsHomJlE';
  }

  const handleVideoClick = (e) => {
    e.preventDefault();
    if (finalVideoUrl) {
      // Convert YouTube URL to embed format
      const videoId = finalVideoUrl.includes('youtube.com') 
        ? finalVideoUrl.split('v=')[1].split('&')[0]
        : finalVideoUrl.split('be/')[1];
      const embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
      setCurrentVideoUrl(embedUrl);
      setShowVideo(true);
      document.body.classList.add('loginpress-video-play');
    }
  };

  const closeVideo = () => {
    setShowVideo(false);
    document.body.classList.remove('loginpress-video-play');
  };

  return (
    <>
      <div className={`loginpress-info-box ${className ? className: ''}`} data-integration={heading}>
        <h3>{__(heading, 'loginpress-pro')}</h3>
        <div className="inside">
          <div className="desc">
            <p>{finalDescription}</p>
          </div>
          {finalVideoUrl && (
            <div className="video">
              <a href={finalVideoUrl} onClick={handleVideoClick}>
                {__('How to Setup', 'loginpress-pro')}
              </a>
            </div>
          )}
        </div>
      </div>

      {showVideo && (
        <div className="loginpress-video-popup">
          <div className="loginpress-cross" onClick={closeVideo}></div>
          <div className="loginpress-video-overlay" onClick={closeVideo}></div>
          <div className="loginpress-video-frame">
            <iframe 
              id="loginpress-video" 
              allow="autoplay" 
              frameBorder="0" 
              src={currentVideoUrl}
              title="LoginPress Video Tutorial"
            ></iframe>
          </div>
        </div>
      )}
    </>
  );
};

export default InfoBox;