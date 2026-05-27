import './sidebar.js';

import {
    moduleIsActive
} from '../utilities';

import {
    UncannyOwlIconColor
} from '../components/icons';

import {
    ToolkitPlaceholder
} from '../components/editor';

const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;


if ( moduleIsActive( `CourseTimer` ) ){

    registerBlockType('uncanny-toolkit-pro/uo-time', {
        title: __( 'Course Cumulative Time', 'uncanny-pro-toolkit' ),

        description: __( 'Displays total time spent inside a LearnDash course, including time spent after completing the course. Optionally, enter a specific user ID and/or course ID.', 'uncanny-pro-toolkit' ),

        icon: UncannyOwlIconColor,

        category: 'uncanny-learndash-toolkit',

        keywords: [
            __('Uncanny Owl', 'uncanny-pro-toolkit'),
        ],

        supports: {
            html: false
        },

        attributes: {
            userId: {
                type: 'string',
                default: ''
            },
            courseId: {
                type: 'string',
                default: ''
            }
        },

        edit({className, attributes, setAttributes}) {
            return (
                <div className={className}>
                    <ToolkitPlaceholder>
                        {__( 'Course Cumulative Time', 'uncanny-pro-toolkit' )}
                    </ToolkitPlaceholder>
                </div>
            );
        },

        save({className, attributes}) {
            // We're going to render this block using PHP
            // Return null
            return null;
        },
    });
}
