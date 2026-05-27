const { __ } = wp.i18n;

const { Fragment } = wp.element;

const { addFilter } = wp.hooks;

const {
    Button,
    FormFileUpload,
    TextControl,
    RadioControl,
    CheckboxControl,
    SelectControl,
    PanelBody
} = wp.components;

const { createHigherOrderComponent } = wp.compose;

const {
    MediaUpload,
    MediaUploadCheck
} = wp.editor;

const {
    InspectorControls
} = wp.blockEditor;

const mediaUpload = wp.editor.mediaUpload;

// Define defaults
const tincannyDefaults = {
    insertAs: 'lightbox',
    openWith: 'button',
    iframe: {
        widthValue:  '100',
        widthUnit:   '%',
        heightValue: '400',
        heightUnit:  'px'
    },
    lightbox: {
        title:       '',
        globalSet:   true,
        widthValue:  vc_snc_data_obj.tincanny_content.lightbox_defaults.widthValue,
        widthUnit:   vc_snc_data_obj.tincanny_content.lightbox_defaults.widthUnit,
        heightValue: vc_snc_data_obj.tincanny_content.lightbox_defaults.heightValue,
        heightUnit:  vc_snc_data_obj.tincanny_content.lightbox_defaults.heightUnit,
        effect:      vc_snc_data_obj.tincanny_content.lightbox_defaults.effect
    },
    button: {
        text:  vc_snc_data_obj.i18n.open,
        size:  'normal',
    },
    image: {
        id:    '',
        title: '',
        sizes: {},
        url:   '',
        isLoading: false
    },
    link: {
        text:   vc_snc_data_obj.i18n.open,
    },
    page: {
        target: '_blank',
    }
}

export const addTinCannyContentSettings = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        // We'll create an array with all the custom
        // components we have to show on the sidebar
        let componentsToShow = [];
        let view = false;

        // Check if we have to do something for selected content.
        if ( isTheTincannyContentBlock( props.name ) ) {
            // Check if we have selected content.
            view = props.isSelected && [ 'has-valid-content' ].includes( props.attributes.status ) ? 'embed-settings' : false;
            // Check if we're on the start screen.
            view = ! view && props.attributes.status == 'start' ? 'start' : view;
        }

        // Prepare data.
        if ( view ) {
            // Create global function to set attributes
            // We will use this function in all our components
            let setAttributes = ( key, value ) => {
                // Create object with attributes to change
                let attributes = {}
                attributes[ key ] = value;

                // Set attributes
                props.setAttributes( attributes );
            }

            // Iterate attributes and remove u0022
            Object.entries( props.attributes ).forEach( ( [ key, attribute ] ) => {
                // Check if it's a string
                if ( typeof attribute == 'string' ){
                    // Replace u0022 with a quote mark
                    props.attributes[ key ] = attribute.replace( /u0022/g, '"' );
                }
            });

            // Pre-upload settings.
            if ( view === 'start' ) {
                componentsToShow.push(
                    <UploadSettings
                        properties={ props.attributes }
                        onChangeDo={ setAttributes }
                    />
                );
            }

            // Embed settings.
            if ( view === 'embed-settings' ) {
                // "Insert As"
                // This one will be shown always
                componentsToShow.push(
                    <InsertAs
                        properties={ props.attributes }
                        onChangeDo={ setAttributes }
                    />
                );

                // "Iframe Settings"
                // Only if "Insert as" is "iframe"
                if ( [ 'iframe' ].includes( props.attributes.insertAs ) ){
                    componentsToShow.push(
                        <IframeSettings
                            properties={ props.attributes }
                            onChangeDo={ setAttributes }
                        />
                    );
                }

                // "Lightbox Settings"
                // Only if "Insert as" is "lightbox"
                if ( [ 'lightbox' ].includes( props.attributes.insertAs ) ){
                    componentsToShow.push(
                        <LightboxSettings
                            properties={ props.attributes }
                            onChangeDo={ setAttributes }
                        />
                    );
                }

                // "Open with"
                // Only if "Insert As" is "lightbox" or "page"
                if ( [ 'lightbox', 'page' ].includes( props.attributes.insertAs ) ){
                    componentsToShow.push(
                        <OpenWith
                            properties={ props.attributes }
                            onChangeDo={ setAttributes }
                        />
                    );

                    // "Button Settings"
                    // Only if "Open with" is "button"
                    if ( [ 'button' ].includes( props.attributes.openWith ) ){
                        componentsToShow.push(
                            <ButtonSettings
                                properties={ props.attributes }
                                onChangeDo={ setAttributes }
                            />
                        );
                    }

                    // "Image Settings"
                    // Only if "Open with" is "image"
                    if ( [ 'image' ].includes( props.attributes.openWith ) ){
                        componentsToShow.push(
                            <ImageSettings
                                properties={ props.attributes }
                                onChangeDo={ setAttributes }
                            />
                        );
                    }

                    // "Link Settings"
                    // Only if "Open with" is "link"
                    if ( [ 'link' ].includes( props.attributes.openWith ) ){
                        componentsToShow.push(
                            <LinkSettings
                                properties={ props.attributes }
                                onChangeDo={ setAttributes }
                            />
                        );
                    }
                }
            }
        }

        // Show settings.
        if( componentsToShow.length > 0 ) {
            return (
                <Fragment>
                    <BlockEdit { ...props } />
                    <InspectorControls>
                        {componentsToShow}
                    </InspectorControls>
                </Fragment>
            );
        }

        // Nothing doing.
        return <BlockEdit { ...props } />;
    };
}, 'addTinCannyContentSettings' );

export const addAttribute = ( settings ) => {
    if ( isTheTincannyContentBlock( settings.name ) ){
        settings.attributes = Object.assign( settings.attributes, {
            fullZipUpload : {
                type:    'boolean',
                default: false
            },
            insertAs: {
                type:    'string',
                default: tincannyDefaults.insertAs
            },
            openWith: {
                type:    'string',
                default: tincannyDefaults.openWith
            },
            iframeSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.iframe )
            },
            lightboxSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.lightbox )
            },
            pageSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.page )
            },
            buttonSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.button )
            },
            imageSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.image )
            },
            linkSettings: {
                type:    'string',
                default: JSON.stringify( tincannyDefaults.link )
            }
        });
    }

    return settings;
}

export const addSaveProps = ( extraProps, blockType, attributes ) => {
    if ( isTheTincannyContentBlock( blockType.name ) ){
        extraProps.fullZipUpload    = attributes.fullZipUpload;
        extraProps.insertAs         = attributes.insertAs;
        extraProps.openWith         = attributes.openWith;
        extraProps.lightboxSettings = attributes.lightboxSettings;
        extraProps.iframeSettings   = attributes.iframeSettings;
        /*extraProps.pageSettings     = attributes.pageSettings;*/
        extraProps.buttonSettings   = attributes.buttonSettings;
        extraProps.imageSettings    = attributes.imageSettings;
        extraProps.linkSettings     = attributes.linkSettings;
    }

    return extraProps;
}

addFilter( 'editor.BlockEdit', 'tincanny/content', addTinCannyContentSettings );
addFilter( 'blocks.registerBlockType', 'tincanny/content', addAttribute );
addFilter( 'blocks.getSaveContent.extraProps', 'tincanny/content', addSaveProps );

export const isTheTincannyContentBlock = ( name ) => {
    return name == 'tincanny/content';
}

/**
 * Components
 */
export const UploadSettings = ({ properties, onChangeDo }) => {
    const isChecked = properties.fullZipUpload ? true : false;
    return (
        <PanelBody title={ __( 'Upload Settings' ) }>
            <CheckboxControl
                label={ __( 'Upload entire zip file' ) }
				help={ __( 'Choose this option for faster uploads; it may not work in all environments or with large file sizes.' ) }
                checked={ isChecked }
                onChange={ ( value ) => {
                    onChangeDo( 'fullZipUpload', value ? 1 : 0 )
                } }
            />
            { isChecked ? (
                <div className="components-base-control">
                    <p>{ tincannyZipUploadData.i18n.max_upload_size }</p>
                </div>
            ) : ("")
            }
        </PanelBody>
    );
}

export const InsertAs = ({ properties, onChangeDo }) => {
    return (
        <PanelBody title={ vc_snc_data_obj.i18n.displayContentIn }>
            <RadioControl
                selected={ properties.insertAs }
                options={[
                    {
                        value: 'iframe',
                        label: vc_snc_data_obj.i18n.iframe
                    },
                    {
                        value: 'lightbox',
                        label: vc_snc_data_obj.i18n.lightbox
                    },
                    {
                        value: 'page',
                        label: vc_snc_data_obj.i18n.newTab
                    },
                 ]}
                onChange={ ( value ) => { onChangeDo( 'insertAs', value ) } }
            />
        </PanelBody>
    );
}

export const OpenWith = ({ properties, onChangeDo }) => {
    // Define default values
    properties = Object.assign( {}, {
        openWith: 'button'
    }, properties );

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.openWith }>
            <RadioControl
                selected={ properties.openWith }
                options={[
                    {
                        value: 'button',
                        label: vc_snc_data_obj.i18n.button
                    },
                    {
                        value: 'image',
                        label: vc_snc_data_obj.i18n.image
                    },
                    {
                        value: 'link',
                        label: vc_snc_data_obj.i18n.link
                    },
                ]}
                onChange={ ( value ) => { onChangeDo( 'openWith', value ) } }
            />
        </PanelBody>
    );
}

export const LightboxSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.lightboxSettings = Object.assign( {}, tincannyDefaults.lightbox, JSON.parse( properties.lightboxSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = ( key, value ) => {
        // Update property
        properties.lightboxSettings[ key ] = value;

        // Return updated object
        return JSON.stringify( properties.lightboxSettings );
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.lightboxSettings }>
            <TextControl
                label={ vc_snc_data_obj.i18n.title }
                value={ properties.lightboxSettings.title }
                type="text"
                onChange={ ( value ) => {
                    onChangeDo( 'lightboxSettings', updateProperties( 'title', value ) )
                }}
            />

            <CheckboxControl
                label={ vc_snc_data_obj.i18n.useGlobalHeightWidth }
                checked={ properties.lightboxSettings.globalSet }
                onChange={ ( value ) => { onChangeDo( 'lightboxSettings', updateProperties( 'globalSet', value ) ) } }
            />

            <DimensionsFields
				type      = "lightbox"
                properties={ properties.lightboxSettings }
                onChangeDo={ ( key, value ) => {
                    onChangeDo( 'lightboxSettings', updateProperties( key, value ) )
                }}
            />

            <SelectControl
                label={ vc_snc_data_obj.i18n.effect }
                value={ properties.lightboxSettings.effect }
                options={[
                    {
                        value: 'fade',
                        label: vc_snc_data_obj.i18n.fade
                    },
					{
                        value: 'zoom',
                        label: vc_snc_data_obj.i18n.zoom
                    },
					{
                        value: 'none',
                        label: vc_snc_data_obj.i18n.none
                    },
                ]}
                onChange={ ( value ) => {
                    onChangeDo( 'lightboxSettings', updateProperties( 'effect', value ) )
                }}
            />
        </PanelBody>
    );
}

export const IframeSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.iframeSettings = Object.assign( {}, tincannyDefaults.iframe, JSON.parse( properties.iframeSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = ( key, value ) => {
        // Update property
        properties.iframeSettings[ key ] = value;

        // Return updated object
        return JSON.stringify( properties.iframeSettings );
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.iframeSettings }>
            <DimensionsFields
				type      = "iframe"
                properties={ properties.iframeSettings }
                onChangeDo={ ( key, value ) => {
                    onChangeDo( 'iframeSettings', updateProperties( key, value ) )
                }}
            />
        </PanelBody>
    );
}

export const DimensionsFields = ({ type, properties, onChangeDo }) => {
    // Define units
    let units = [
        {
            value: '%',
            label: '%'
        },
        {
            value: 'px',
            label: 'px'
        },
        {
            value: 'vw',
            label: 'vw'
        },
        {
            value: 'vh',
            label: 'vh'
        },
    ];

	const useGlobalSettings = ( type === 'lightbox' && properties.globalSet === true ) ? false : true;

    return (
        <Fragment>
            { useGlobalSettings ? (
                <div className="components-base-control">
                    <div className="uo-tclr-gutenberg-field-with-unit">
                        <div className="uo-tclr-gutenberg-field-with-unit__number">
                            <TextControl
                                label={ vc_snc_data_obj.i18n.width }
                                value={ properties.widthValue }
                                type="number"
                                onChange={ ( value ) => { onChangeDo( 'widthValue', value ) } }
                            />
                        </div>
                        <div className="uo-tclr-gutenberg-field-with-unit__select">
                            <SelectControl
                                value={ properties.widthUnit }
                                options={ units }
                                onChange={ ( value ) => { onChangeDo( 'widthUnit', value ) } }
                            />
                        </div>
                    </div>
                    <div className="uo-tclr-gutenberg-field-with-unit">
                        <div className="uo-tclr-gutenberg-field-with-unit__number">
                            <TextControl
                                label={ vc_snc_data_obj.i18n.height }
                                value={ properties.heightValue }
                                type="number"
                                onChange={ ( value ) => { onChangeDo( 'heightValue', value ) } }
                            />
                        </div>
                        <div className="uo-tclr-gutenberg-field-with-unit__select">
                            <SelectControl
                                value={ properties.heightUnit }
                                options={ units }
                                onChange={ ( value ) => { onChangeDo( 'heightUnit', value ) } }
                            />
                        </div>
                    </div>
                </div>
             ) : ("")}
        </Fragment>
    );
}

export const ButtonSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.buttonSettings = Object.assign( {}, tincannyDefaults.button, JSON.parse( properties.buttonSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = ( key, value ) => {
        // Update property
        properties.buttonSettings[ key ] = value;

        // Return updated object
        return JSON.stringify( properties.buttonSettings );
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.buttonSettings }>
            <TextControl
                label={ vc_snc_data_obj.i18n.text }
                value={ properties.buttonSettings.text }
                type="text"
                onChange={ ( value ) => {
                    onChangeDo( 'buttonSettings', updateProperties( 'text', value ) )
                }}
            />

            <RadioControl
                label={ vc_snc_data_obj.i18n.size }
                selected={ properties.buttonSettings.size }
                options={[
                    {
                        value: 'small',
                        label: vc_snc_data_obj.i18n.small
                    },
                    {
                        value: 'normal',
                        label: vc_snc_data_obj.i18n.normal
                    },
                    {
                        value: 'big',
                        label: vc_snc_data_obj.i18n.big
                    }
                 ]}
                onChange={ ( value ) => {
                    onChangeDo( 'buttonSettings', updateProperties( 'size', value ) )
                }}
            />
        </PanelBody>
    );
}

export const ImageSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.imageSettings = Object.assign( {}, tincannyDefaults.image, JSON.parse( properties.imageSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = () => {
        // Return updated object
        return JSON.stringify( properties.imageSettings );
    }

    // Create image preview
    let imagePreview = [];

    if ( properties.imageSettings.url !== '' ){
        imagePreview.push((
            <div className="uo-tclr-gutenberg-image__preview">
                <div className="uo-tclr-gutenberg-image-preview__block">
                    <img src={ properties.imageSettings.url }/>
                </div>
                <div className="uo-tclr-gutenberg-image-preview__title">
                    { properties.imageSettings.title }
                </div>
            </div>
        ));
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.imageSettings }>
            <div className="components-base-control">
                <MediaUploadCheck>
                    <div className="uo-tclr-gutenberg-image">
                        { imagePreview }
                        <div className="uo-tclr-gutenberg-image__upload">
                            <FormFileUpload
                                isDefault
                                isBusy={ properties.imageSettings.isLoading }
                                accept="image/*"
                                onChange={ ( event ) => {
                                    // Change to "busy"
                                    properties.imageSettings.isLoading = true;
                                    onChangeDo( 'lightboxSettings', updateProperties() );

                                    // Upload
                                    mediaUpload({
                                        allowedTypes: [ 'image' ],
                                        filesList: event.target.files,
                                        onFileChange: ( media ) => {
                                            // Get first file
                                            media = media[0]

                                            // Add media data
                                            properties.imageSettings = Object.assign({}, properties.imageSettings, {
                                                id:    media.id,
                                                title: media.title,
                                                sizes: media.sizes,
                                                url:   media.url,
                                            });

                                            // Delete "isLoading"
                                            delete properties.imageSettings.isLoading;

                                            // Update props
                                            onChangeDo( 'imageSettings', updateProperties() );
                                        }
                                    });
                                }}
                            >
                                { vc_snc_data_obj.i18n.upload }
                            </FormFileUpload>
                        </div>
                        <div className="uo-tclr-gutenberg-image__select">
                            <MediaUpload
                                onSelect={ ( media ) => {
                                    // Add media data
                                    properties.imageSettings = Object.assign({}, properties.imageSettings, {
                                        id:    media.id,
                                        title: media.title,
                                        sizes: media.sizes,
                                        url:   media.url,
                                    });

                                    // Delete "isLoading"
                                    delete properties.imageSettings.isLoading;

                                    // Update props
                                    onChangeDo( 'imageSettings', updateProperties() );
                                }}
                                type="image"
                                className="editor-media-placeholder__button"
                                render={ ( { open } ) => (
                                    <Button variant="secondary" onClick={ open }>
                                        { vc_snc_data_obj.i18n.mediaLibrary }
                                    </Button>
                                ) }
                            />
                        </div>
                    </div>
                </MediaUploadCheck>
            </div>
        </PanelBody>
    );
}

export const LinkSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.linkSettings = Object.assign( {}, tincannyDefaults.link, JSON.parse( properties.linkSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = ( key, value ) => {
        // Update property
        properties.linkSettings[ key ] = value;

        // Return updated object
        return JSON.stringify( properties.linkSettings );
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.linkSettings }>
            <TextControl
                label={ vc_snc_data_obj.i18n.text }
                value={ properties.linkSettings.text }
                type="text"
                onChange={ ( value ) => {
                    onChangeDo( 'linkSettings', updateProperties( 'text', value ) )
                }}
            />
        </PanelBody>
    );
}

export const PageSettings = ({ properties, onChangeDo }) => {
    // Clone properties
    properties = JSON.parse( JSON.stringify( properties ) );

    // Define default values
    properties.pageSettings = Object.assign( {}, tincannyDefaults.page, JSON.parse( properties.pageSettings ));

    // Create function to update the properties object before sending the
    // updated version to the main props object
    let updateProperties = ( key, value ) => {
        // Update property
        properties.pageSettings[ key ] = value;

        // Return updated object
        return JSON.stringify( properties.pageSettings );
    }

    return (
        <PanelBody title={ vc_snc_data_obj.i18n.pageSettings }>
            <RadioControl
                label={ vc_snc_data_obj.i18n.openIn }
                selected={ properties.pageSettings.target }
                options={[
                    {
                        value: '_self',
                        label: vc_snc_data_obj.i18n.sameWindow
                    },
                    {
                        value: '_blank',
                        label: vc_snc_data_obj.i18n.newWindow
                    },
                 ]}
                onChange={ ( value ) => {
                    onChangeDo( 'pageSettings', updateProperties( 'target', value ) )
                }}
            />
        </PanelBody>
    );
}

/**
 * Helper functions
 */

export const isDefined = ( variable ) => {
    return variable !== undefined && variable !== null;
}
