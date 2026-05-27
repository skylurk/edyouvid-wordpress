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

export const addCeuDaysRemainingSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Check if we have to do something
        if (props.name == 'uncanny-ceu/uo-ceu-days-remaining' && props.isSelected) {
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody title={ __( 'Days Remaining', 'uncanny-ceu' )}>
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
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        }

        return <BlockEdit {...props} />;
    };
}, 'addGroupsUOSettings');

addFilter('editor.BlockEdit', 'uncanny-ceu/uo-ceu-days-remaining', addCeuDaysRemainingSettings);