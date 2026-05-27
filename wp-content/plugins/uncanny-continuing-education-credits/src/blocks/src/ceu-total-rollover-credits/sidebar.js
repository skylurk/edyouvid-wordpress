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

export const addTotalRolloverSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Check if we have to do something
        if (props.name == 'uncanny-ceu/uo-ceu-total-rollover' && props.isSelected) {
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody title={__('Total Rollover Credits Settings', 'uncanny-ceu')}>
                            <TextControl
                                label={__('User ID', 'uncanny-ceu')}
                                value={props.attributes.userId}
                                type="number"
                                onChange={(value) => {
                                    props.setAttributes({
                                        userId: value
                                    });
                                }}
                            />

                            <SelectControl
                                label={__('On or After Rollover', 'uncanny-ceu')}
                                value={props.attributes.rollover}
                                options={[
                                    {label: 'After', value: 'after'},
                                    {label: 'Before', value: 'before'},
                                ]}
                                onChange={(value) => {
                                    props.setAttributes({rollover: value});
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

addFilter('editor.BlockEdit', 'uncanny-ceu/uo-ceu-total-rollover', addTotalRolloverSettings);