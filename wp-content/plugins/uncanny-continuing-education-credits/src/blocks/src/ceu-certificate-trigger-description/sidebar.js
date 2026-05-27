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

export const addCeuCertificateDescriptionSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Check if we have to do something
        if (props.name == 'uncanny-ceu/uo-ceu-certificate-description' && props.isSelected) {
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>

                        <PanelBody title={__('Certificate Trigger Description Settings', 'uncanny-ceu')}>

                            <TextControl
                                label={ __('Certificate ID', 'uncanny-ceu' )}
                                value={props.attributes.certificateId}
                                type="number"
                                onChange={(value) => {
                                    props.setAttributes({
                                        certificateId: value
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

addFilter('editor.BlockEdit', 'uncanny-ceu/uo-ceu-certificate-description', addCeuCertificateDescriptionSettings);