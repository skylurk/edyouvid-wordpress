import './sidebar.js';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    UcecPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

registerBlockType( 'uncanny-ceu/uo-ceu-certificate-description', {
    title: __( 'Certificate Trigger Description', 'uncanny-ceu' ),

    description: __( "Displays the certificate's trigger description from the Edit Certificate screen.", 'uncanny-ceu' ),

    icon: UncannyOwlIconColor,

    category: 'uncanny-ceu',

    keywords: [
        __( 'Uncanny Owl', 'uncanny-ceu' ),
    ],

    supports: {
        html: false
    },

    attributes: {
        certificateId: {
            type:    'string',
            default: ''
        },
    },

    edit({ className, attributes, setAttributes }){
        return (
            <div className={className}>
                <UcecPlaceholder>
                    { __( 'Certificate Trigger Description', 'uncanny-ceu' ) }
                </UcecPlaceholder>
            </div>
        );
    },

    save({ className, attributes }){
        // We're going to render this block using PHP
        // Return null
        return null;
    },
});