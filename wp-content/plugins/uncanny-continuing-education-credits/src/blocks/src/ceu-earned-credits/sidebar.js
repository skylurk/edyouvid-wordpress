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

export const addCeuEarnedSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Check if we have to do something
        if (props.name == 'uncanny-ceu/uo-ceu-earned' && props.isSelected) {
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>

                        <PanelBody title={ __( 'Earned Credits Settings', 'uncanny-ceu' )}>

                            <TextControl
                                label={ __( 'Course ID', 'uncanny-ceu' )}
                                value={props.attributes.courseId}
                                type="number"
                                onChange={(value) => {
                                    props.setAttributes({
                                        courseId: value
                                    });
                                }}
                            />

                            <TextControl
                                label={ __( 'User ID', 'uncanny-ceu' )}
                                value={props.attributes.userId}
                                type="number"
                                onChange={(value) => {
                                    props.setAttributes({
                                        userId: value
                                    });
                                }}
                            />

                            <SelectControl
                                label={ __( 'Since Rollover', 'uncanny-ceu' )}
                                value={props.attributes.sinceRollover}
                                options={[
                                    {label: 'Yes', value: 'yes'},
                                    {label: 'No', value: 'no'},
                                ]}
                                onChange={(value) => {
                                    props.setAttributes({
                                        sinceRollover: value
                                    });
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

addFilter('editor.BlockEdit', 'uncanny-ceu/uo-ceu-earned', addCeuEarnedSettings);