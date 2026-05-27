const {__} = wp.i18n;

const {
    addFilter
} = wp.hooks;

const {
    SelectControl,
    PanelBody,
    TextControl
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

export const addCeuAvailableSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Check if we have to do something
        if (props.name == 'uncanny-ceu/uo-ceu-available' && props.isSelected) {
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody title={ __( 'Available Credits Settings', 'uncanny-ceu' ) }>
                            <TextControl
                                label={ __( 'Course ID', 'uncanny-ceu' )}
                                value={ props.attributes.courseId }
                                type="number"
                                onChange={(value) => {
                                    props.setAttributes({ courseId: value });
                                }}
                            />
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        }

        return <BlockEdit {...props} />;
    };
}, 'addGroupsUOSettings');

addFilter('editor.BlockEdit', 'uncanny-ceu/uo-ceu-available', addCeuAvailableSettings);