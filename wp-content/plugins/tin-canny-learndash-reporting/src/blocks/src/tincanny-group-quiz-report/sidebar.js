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
} = wp.blockEditor;

export const addTincannyGroupQuizReportSettings = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        // Check if we have to do something
        if ( props.name == 'tincanny/group-quiz-report' && props.isSelected ){
            return (
                <Fragment>
                    <BlockEdit { ...props } />
                    <InspectorControls>

                        <PanelBody title={ vc_snc_data_obj.i18n.groupQuizReportSettings }>
                            <TextControl label={ vc_snc_data_obj.i18n.userQuizReportURL } value={ props.attributes.user_report_url } type='text' onChange={ ( value ) => { props.setAttributes({ user_report_url: value }); }} />
				        </PanelBody>

                    </InspectorControls>
                </Fragment>
            );
        }



        return <BlockEdit { ...props } />;
    };
}, 'addTincannyGroupQuizReportSettings' );

addFilter( 'editor.BlockEdit', 'tincanny/group-quiz-report', addTincannyGroupQuizReportSettings );
