import {
    moduleIsActive
} from '../utilities';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    ToolkitPlaceholder
} from '../components/editor';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

if ( moduleIsActive( `DownloadCertificatesInBulk` ) ) {

    registerBlockType( 'uncanny-toolkit-pro/download-certificates-bulk', {

        title: __( 'Download certificates in bulk', 'uncanny-pro-toolkit' ),

        description: __( 'A new module to download course/quiz completion certificates in bulk as a zip for to frontend.', 'uncanny-pro-toolkit' ),

        icon: UncannyOwlIconColor,

        category: 'uncanny-learndash-toolkit',

        keywords: [
            __( 'Uncanny Owl', 'uncanny-pro-toolkit' ),
        ],

        supports: {
            html: false
        },

        attributes: {},

        edit({ className, attributes, setAttributes }) {
            return (
                <div className={className}>
                    <ToolkitPlaceholder>
                        { __( 'Download certificates in bulk', 'uncanny-pro-toolkit' ) }
                    </ToolkitPlaceholder>
                </div>
            );
        },

        save({ className, attributes }) {
            // We're going to render this block using PHP
            // Return null
            return null;
        },
    });
}
