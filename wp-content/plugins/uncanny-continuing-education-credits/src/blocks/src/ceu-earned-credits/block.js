import './sidebar.js';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    UcecPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

registerBlockType( 'uncanny-ceu/uo-ceu-earned', {
    title: __( 'Earned Credits', 'uncanny-ceu' ),

    description: __( 'Displays the number of credits earned for the course.', 'uncanny-ceu' ),

    icon: UncannyOwlIconColor,

    category: 'uncanny-ceu',

    keywords: [
        __( 'Uncanny Owl', 'uncanny-ceu' ),
    ],

    supports: {
        html: false
    },

    attributes: {
        courseId: {
            type:    'string',
            default: ''
        },
        userId: {
            type:    'string',
            default: ''
        },
        sinceRollover: {
            type:    'string',
            default: 'yes'
        },
    },

    edit({ className, attributes, setAttributes }){
        return (
            <div className={className}>
                <UcecPlaceholder>
                    { __( 'Earned Credits', 'uncanny-ceu' )}
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