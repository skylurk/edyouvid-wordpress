const GLightbox = require('glightbox.js');
import 'glightbox.css';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize iframes with data-src attribute
    document.querySelectorAll('.uo-tincanny-content iframe').forEach(iframe => {
        const src = iframe.getAttribute('data-src');
        iframe.setAttribute('src', src);
    });

    // Function to resize the lightbox content
    function resizeLightbox() {
        const wrap = document.querySelector('.glightbox-container');
        const content = document.querySelector('.gslide.current iframe');
        if (wrap && content) {
            const wrapHeight = wrap.offsetHeight;
            const contentHeight = content.offsetHeight;
            // Avoid adding padding when width and height are 100%
            if (content.style.width === '100%' && content.style.height === '100%') {
                wrap.style.paddingTop = '0px';
            } else if (wrapHeight > contentHeight) {
                wrap.style.paddingTop = `${(wrapHeight - contentHeight) / 2}px`;
            } else {
                wrap.style.paddingTop = '0px';
            }
        }
    }

	 // Function to smoothly adjust height
	function setContentHeight(element, height) {
        element.style.transition = 'height 0.5s ease-in-out';
        element.style.height = height;
    }

    function initializeLGightbox(link) {
        const href = link.getAttribute('href');
        const width = link.getAttribute('data-width') || '50%';
        const height = link.getAttribute('data-height') || '50%';
        const transition = link.getAttribute('data-transition');
        const title = link.getAttribute('title');
        let _transition = transition;

        if ('zoom' !== transition && 'fade' !== transition && 'none' !== transition) {
            _transition = 'zoom';
        }

        // Destroy the previous instance if it exists
        if (typeof lightboxInstance !== 'undefined') {
            lightboxInstance.destroy();
        }

        var closeOnOutsideClick = false;
        if (typeof Tincanny === 'object' && Tincanny !== null && 
            typeof Tincanny.closeOnOutsideClick !== 'undefined' && Tincanny.closeOnOutsideClick === '1') {
            closeOnOutsideClick = true;
        }

        // Initialize a new GLightbox instance with the specific link's data
        const lightboxInstance = GLightbox({
            elements: [{
                href: href,
                width: width,
                height: height
            }],
            touchNavigation: false,
            keyboardNavigation: false,
            loop: false,
            openEffect: _transition,
            closeEffect: _transition,
            closeOnOutsideClick: closeOnOutsideClick
        });

        return lightboxInstance;
    }

    // Initialize GLightbox with custom options for each link
    document.querySelectorAll('a.glightbox').forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent default link behavior

            const lightboxInstance = initializeLGightbox(link);

            lightboxInstance.on('open', () => {
                // Add a class to the body element for conditional styling
                document.body.classList.add('tclr-lightbox-open');
                
                const width = link.getAttribute('data-width');
                const height = link.getAttribute('data-height');
                
                // Apply dynamic styles to elements matching the selector
                const elements = document.querySelectorAll('.glightbox-container .gslide, .glightbox-container .gslide-inner-content');
            
                elements.forEach(element => {
                    element.style.height = height; // Dynamically set height
                    element.style.width = width;  // Optionally set width if needed
                });
            });

            lightboxInstance.on('slide_changed', () => {

                document.dispatchEvent(new CustomEvent('tclr/module/lightbox/open'));

                resizeLightbox();
                window.addEventListener('resize', resizeLightbox);

            });

            lightboxInstance.once('close', () => {
                document.body.classList.remove('tclr-lightbox-open');
                document.dispatchEvent(new CustomEvent('tclr/module/lightbox/close'));
                window.removeEventListener('resize', resizeLightbox);
            });

            // Open the lightbox manually
            lightboxInstance.open();
        });
    });
});
