import './sidebar.js';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    UcecPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

registerBlockType( 'uncanny-ceu/uo-ceu-days-remaining', {
    title: __( 'Days Remaining', 'uo-ceu-days-remaining' ),

    description: __( 'Displays the number of days until the rollover date.', 'uncanny-ceu' ),

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
                    { __( 'Days Remaining', 'uncanny-ceu' ) }
                </UcecPlaceholder>
            </div>
        );
    },

    save({className, attributes}){
        // We're going to render this block using PHP
        // Return null
        return null;
    },
});