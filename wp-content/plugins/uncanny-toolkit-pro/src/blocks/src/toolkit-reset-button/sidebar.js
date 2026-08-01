const { __ } = wp.i18n;

const {
	addFilter
} = wp.hooks;

const {
	PanelBody,
	TextControl,
    SelectControl
} = wp.components;

const {
	Fragment
} = wp.element;

const {
	createHigherOrderComponent
} = wp.compose;

const {
    InspectorControls
} = wp.editor;

import {
    isBoolean
} from '../utilities';

export const addToolkitResetButtonSettings = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        // Check if we have to do something
        if ( props.name == 'uncanny-toolkit-pro/reset-button' && props.isSelected ){
            return (
                <Fragment>
                    <BlockEdit { ...props } />
                    <InspectorControls>

                        <PanelBody title={ __( 'Reset Button settings', 'uncanny-pro-toolkit' ) }>
                            <TextControl
                                label={ __( 'Course ID', 'uncanny-pro-toolkit' ) }
                                value={ props.attributes.courseId }
                                help={ __( 'When placed on a course page, you do not need to pass in the course ID attribute.', 'uncanny-pro-toolkit' ) }
                                type="string"
                                onChange={ ( value ) => {
                                    props.setAttributes({
                                        courseId: value
                                    });
                                }}
                            />

                            <SelectControl
                                label={ __( 'Reset TinCanny data', 'uncanny-pro-toolkit' ) }
                                value={ props.attributes.resetTincanny }
                                help={ __( 'If the reset_tincanny parameter is set to "Yes", clicking the reset button will also delete all Tin Canny results records for that user in the target course. Note: Due to technical constraints, Tin Canny bookmark (resume) data will not be deleted.', 'uncanny-pro-toolkit' ) }
                                options={[
                                    {
                                        value: 'no',
                                        label: __( 'No' ) 
                                    },
                                    {
                                        value: 'yes',
                                        label: __( 'Yes' ) 
                                    },
                                ]}
                                onChange={ ( value ) => {
                                    props.setAttributes({
                                        resetTincanny: value
                                    })
                                }}
                            />

                            <TextControl
                                label={ __( 'Redirect', 'uncanny-pro-toolkit' ) }
                                value={ props.attributes.redirect }
                                help={ __( 'Redirect to the specified URL when clicked.', 'uncanny-pro-toolkit' ) }
                                type="string"
                                onChange={ ( value ) => {
                                    props.setAttributes({
                                        redirect: value
                                    })
                                }}
                            />
				        </PanelBody>

                    </InspectorControls>
                </Fragment>
            );
        }

        return <BlockEdit { ...props } />;
    };
}, 'addToolkitResetButtonSettings' );

addFilter( 'editor.BlockEdit', 'uncanny-toolkit-pro/reset-button', addToolkitResetButtonSettings );