let videoBlockElementGallery = wp.element.createElement;
let uid_g = "uid" + Math.random().toString(16).slice(2)

function buildQueryString(data) {
    let string = '';

    const stack = Object.entries(data);

    let pair;
    while ((pair = stack.shift())) {
        let [key, value] = pair;
        const hasNestedData =
            Array.isArray(value) || (value && value.constructor === Object);
        if (hasNestedData) {
            const valuePairs = Object.entries(value).reverse();
            for (const [member, memberValue] of valuePairs) {
                stack.unshift([`${key}[${member}]`, memberValue]);
            }
        } else if (value !== undefined) {
            if (value === null) {
                value = '';
            }
            string +=
                '&' + [key, value].map(encodeURIComponent).join('=');
        }
    }
    return string.substr(1);
}

function rendererPath(block, attributes = null, urlQueryArgs = {}) {
    return addQueryArgs(`/wp/v2/block-renderer/${block}`, {
        context: 'edit',
        ...(null !== attributes ? {attributes} : {}),
        ...urlQueryArgs,
    });
}

function addQueryArgs(url = '', args) {
    // If no arguments are to be appended, return original URL.
    if (!args || !Object.keys(args).length) {
        return url;
    }

    let baseUrl = url;

    // Determine whether URL already had query arguments.
    const queryStringIndex = url.indexOf('?');
    if (queryStringIndex !== -1) {
        // Merge into existing query arguments.
        args = Object.assign(getQueryArgs(url), args);

        // Change working base URL to omit previous query arguments.
        baseUrl = baseUrl.substr(0, queryStringIndex);
    }

    return baseUrl + '?' + buildQueryString(args);
}

function createTemplateGallery(data){

    let template= '<div id="'+uid_g+'">' +
        '<div>' +
        '    <table id="spotlightr_gallery_table">' +
        '        <thead>' +
        '        <tr>' +
        '            <th class="spotlightr-gallery-thumbnail"></th>' +
        '            <th>Title</th>' +
        '            <th class="spotlightr-gallery-actions">Actions</th>' +
        '        </tr>' +
        '        </thead>' +
        Object.keys(data.galleries).map(function (key) {
            let value = data.galleries[key]
            return '<tr>' +
                '        <td>' +
                '                <img src="'+ value.settings['background'] +'" height="40">' +
                '        </td>' +
                '        <td>' +
                '            <span class="spotlightr-gallery-title">'+value.name+'</span>' +
                '        </td>' +
                '        <td>' +
                '            <span class="spotlightr-gallery-video-count">'+value.numberOfVideos+'</span>' +
                '        </td>' +
                '        <td>' +
                '            <span class="spotlightr-gallery-publish spotlightr-block-publish" data-encode-id="'+btoa(value.id)+'"' +
                '                  data-subdomain="' + data.subdomain + '" data-toggle="modal" data-target="#'+uid_g+'_block_modal"><i' +
                '                class="dashicons dashicons-admin-links"></i></span>' +
                '        </td>' +
                '    </tr>'}).join('')+
        '</table>'+
        '    <div class="modal spotlightr-modal-fade" id="'+uid_g+'_block_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">' +
        '        <div class="modal-dialog spotlightr-modal-dialog" role="document">' +
        '            <div class="modal-content spotlightr-modal-content">' +
        '                <div class="modal-header">' +
        '                    <h4 class="modal-title" id="'+uid_g+'_myModalLabel">Publish Options</h4>' +
        '                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span' +
        '                            aria-hidden="true">&times;</span></button>' +
        '                </div>' +
        '                <div class="modal-body spotlightr-modal-body spotlightr-block-modal-body">' +
        '                    <div class="spotlightr-modal-longcode-wraper" style="display: none">' +
        '                        <h5>Longcode</h5>' +
        '                        <textarea class="'+uid_g+'-block-modal-longcode" id="'+uid_g+'_block_modal_longcode"><script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js">'+
        '                       </script><a class="channelIframe videoPlayer" href="" data-playerid="encode_id"></a></textarea>' +
        '                    </div>' +
        '                       <h3>There are no additional options for gallery</h3>' +
        '                    <button id="'+uid_g+'_copy_longcode" class="spotlightr-copy spotlightr-button">Publish Gallery</button>' +
        '                </div>' +
        '            </div>' +
        '        </div>' +
        '</div>'

    return template;
}



const galleryIcon = videoBlockElementGallery('img', {src: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAASlQTFRFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVCeTqwAAAGN0Uk5TAARbNab//jABQSn67X0SNvWT/OJFDdqi83qJVR7hbJwDe+Zw2QwF1dhI+1lE/bsjt7LykTfT9n5OgxGw+egJyuSPCPTFWFxXBzoYaTG6ghVTBkKY40buznIf7JVm8SQih8IaQwx6HQAAAPhJREFUeJxt0OdWwkAQBeALcmMEJKLGXgDFgiJiF1CkWlDB3uv7PwQbICHJZn7smTPf7s6ZAQCffwCeEaASGFSNbCjogBDJ8HAE0EaiDhilEWPj+gQnPWBKmyZnZAjNzs2TCzIsxuIKmVjSXbCcXFnt/Li2rtohtYHNNLuxlbHBdgxZ7uzuRfaFHBz24Qg49udEnjfeFCw4OTVfFw04s6Bk1ssVUqlqFtRMqIvr5xf95pfivGpcAzfh26Z9jtbd/cNj+gmZ3pYtSD2Lli86co6N9JbIV7ijC5U3Cd478CHV8ZkQ+/v6lgHJHyq/HnUxA//KnqDW/92lNmqHGSP6I/X3AAAAAElFTkSuQmCC'});

wp.blocks.registerBlockType('spotlightr/gallery',
    {
        title: 'Spotlightr Gallery',
        icon: galleryIcon,
        category: 'media',
        attributes: {
            content: {
                type: 'html',
                default: ''
            },
            is_loading:{
                type: 'boolean',
                default: false
            },
            content_preview: {
                type: 'html',
                default: ''
            },
        },
        edit: (props) => {
            let content_block = props.attributes.content===''?
                videoBlockElementGallery(wp.element.RawHTML,{}, props.attributes.content_preview)
                :
                videoBlockElementGallery(wp.components.SandBox,{
                    html:props.attributes.content});

            let url_p = rendererPath('spotlightr/gallery-block')
            if(!props.attributes.is_loading) {
                props.setAttributes({content_preview: 'Loading', is_loading: true})
                wp.apiFetch({
                    path: url_p,
                    method: 'POST',
                }).then((fetchResponse) => {
                    if (fetchResponse) {
                        let data = JSON.parse(fetchResponse.rendered)
                        if (data.error_message === true){
                            let content_p = '<div><p>You need to authorize to<a href="'+data.error_url+'" target="_blank"> Spotlightr plugin</a></p></div>'
                            props.setAttributes({content_preview: content_p, is_loading: true})
                        } else {
                            let content_p = createTemplateGallery(data);
                            props.setAttributes({content_preview: content_p, is_loading: true})
                            jQuery(document).on('click', '#'+uid_g+' .spotlightr-block-publish', function () {
                                let galleryLongcodeExample = '<script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js">'+
                                    '                       </script><a class="channelIframe videoPlayer" href="" data-playerid="encode_id"></a>';
                                let galleryEncodeId = jQuery(this).data('encodeId');
                                let gallerySubdomain = jQuery(this).data('subdomain');
                                let galleryLongcode = galleryLongcodeExample.replaceAll('encode_id', galleryEncodeId).replaceAll('subdomain_name', gallerySubdomain);
                                jQuery('#'+uid_g+'_block_modal_longcode').text(galleryLongcode);
                            });

                            jQuery(document).on('click', '#'+uid_g+'_copy_longcode', function (event) {
                                let code_g = jQuery('#'+uid_g+'_block_modal_longcode').text();
                                jQuery(this).closest('#'+uid_g+'_block_modal').first().modal('hide');
                                props.setAttributes({content: code_g, content_preview: code_g})
                            });
                        }
                    }
                })
            }

            return   content_block;
        },
        save:  (props)=> {

            return  videoBlockElementGallery(wp.element.RawHTML,{}, props.attributes.content);
        },

    }
)
