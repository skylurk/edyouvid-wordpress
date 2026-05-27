import './sidebar.js';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    UcecPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

registerBlockType( 'uncanny-ceu/uo-ceu-total', {
    title: __( 'Total Credits', 'uncanny-ceu' ),

    description: __( 'Displays the total number of credits earned by the current user regardless of rollover date.', 'uncanny-ceu' ),

    icon: UncannyOwlIconColor,

    category: 'uncanny-ceu',

    keywords: [
        __( 'Uncanny Owl', 'uncanny-ceu' ),
    ],

    supports: {
        html: false
    },

    attributes: {
        userId: {
            type:    'string',
            default: ''
        },
    },

    edit({ className, attributes, setAttributes }){
        return (
            <div className={className}>
                <UcecPlaceholder>
                    { __( 'Total Credits', 'uncanny-ceu' ) }
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