let videoBlockElement = wp.element.createElement;
let uid = "uid" + Math.random().toString(16).slice(2)
let editProps = null;

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

function createTemplate(data){

    let template= '<div id="'+uid+'">' +
        Object.keys(data.group_list).map(function (key) {
            let value = data.group_list[key]
            return '            <div>' +
                '                <div class="'+uid+'-spotlightr-group-row spotlightr-group-row" data-id="'+ key +'">' +
                '                    <span class="spotlightr-group-title"><i' +
                '                            class="dashicons dashicons-open-folder"></i>'+value.name+'</span>' +
                '                    <span class="spotlightr-group-video-count">'+value.numberOfVideos+' videos</span>' +
                '                </div>' +
                '</div>'

        }).join('')+

        '    <div class="modal spotlightr-modal-fade" id="'+uid+'_block_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">' +
        '        <div class="modal-dialog spotlightr-modal-dialog" role="document">' +
        '            <div class="modal-content spotlightr-modal-content">' +
        '                <div class="modal-header">' +
        '                    <h4 class="modal-title" id="'+uid+'_myModalLabel">Publish Options</h4>' +
        '                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span' +
        '                            aria-hidden="true">&times;</span></button>' +
        '                </div>' +
        '                <div class="modal-body spotlightr-modal-body spotlightr-block-modal-body">' +
        '                    <div class="spotlightr-modal-longcode-wraper" style="display: none">' +
        '                        <h5>Longcode</h5>' +
        '                        <textarea class="'+uid+'-block-modal-longcode"><script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js"></script><iframe ' +
                                    'allow="autoplay" class="video-player-container spotlightr" data-playerid="encode_id" allowtransparency="true" style="max-width:100%" ' +
                                    'name="videoPlayerframe" allowfullscreen="true" src="https://subdomain_name.cdn.spotlightr.com/watch/encode_id?start_time&end_time&aspect&resolution" ' +
                                    'watch-type="" url-params="start_time&end_time&aspect&resolution" frameborder="0" scrolling="no"></iframe></textarea>' +
        '                    </div>' +
        '                       <h5>You can choose additional options</h5>' +
        '                    <div>' +
        '                        <label for="'+uid+'_block_aspect_checkbox">Custom aspect ratio</label>' +
        '                        <input type="checkbox" id="'+uid+'_block_aspect_checkbox" class="checkbox">' +
        '                        <div class="spotlightr-block-aspect-select-wrapper" style="display: none">' +
        '                            <h6>Select aspect ratio</h6>' +
        '                            <label for="'+uid+'_block_aspect_select">Aspect size Presets</label>' +
        '                            <select id="'+uid+'_block_aspect_select">' +
                                        Object.keys(data.custom_aspects).map(function (key) {
                                            let value_custom_aspects = data.custom_aspects[key]
                                            return '<option' +
        '                                   value="' + value_custom_aspects.value + '">' + value_custom_aspects.label + '(' + value_custom_aspects.ratio +') </option>'
                                        }).join('')+
        '                            </select>' +
        '                        </div>' +
        '                    </div>' +
        '                    <hr/>' +
        '                    <div>' +
        '                        <label for="block_time_checkbox">Custom start/end time</label>' +
        '                        <input type="checkbox" id="'+uid+'_block_time_checkbox" class="checkbox">' +
        '                        <div class="block-time-select-wrapper" style="display: none">' +
        '                            <h6>Start time</h6>' +
        '                            <label for="'+uid+'_start_hour">Hours</label>' +
        '                            <input id="'+uid+'_start_hour" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                            <label for="'+uid+'_start_minute">Minutes</label>' +
        '                            <input id="'+uid+'_start_minute" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                            <label for="'+uid+'_start_second">Seconds</label>' +
        '                            <input id="'+uid+'_start_second" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                            <h6>End time</h6>' +
        '                            <label for="'+uid+'_end_hour">Hours</label>' +
        '                            <input id="'+uid+'_end_hour" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                            <label for="'+uid+'_end_minute">Minutes</label>' +
        '                            <input id="'+uid+'_end_minute" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                            <label for="'+uid+'_end_second">Seconds</label>' +
        '                            <input id="'+uid+'_end_second" class="spotlightr-time-input" type="number" min="0" value="0">' +
        '                        </div>' +
        '                    </div>' +
        '' +
        '                    <div class="block-resolution-wraper" style="display: none">' +
        '                        <hr/>' +
        '                        <label for="block_resolution_checkbox">Custom resolution</label>' +
        '                        <input type="checkbox" id="'+uid+'_block_resolution_checkbox" class="checkbox">' +
        '                        <div class="block-resolution-select-wrapper" style="display: none">' +
        '                            <h6>Select resolution</h6>' +
        '                            <label for="block_resolution_select">Resolution</label>' +
        '                            <select id="'+uid+'_block_resolution_select">' +
        '                            </select>' +
        '                        </div>' +
        '                    </div>' +
        '                    <hr/>' +
        '                    <button data-id="copy-longcode-uid" id="'+uid+'_copy_longcode" class="spotlightr-copy spotlightr-button">&nbsp; Publish Video &nbsp;</button>' +
        '                </div>' +
        '            </div>' +
        '        </div>' +
        '    </div>' +
        '</div>'

    return template;
}
jQuery(document).on('click','[data-id="copy-longcode-uid"]', function (event) {
	let currentUid = jQuery(event.currentTarget).attr('id')
	currentUid = currentUid.replace('_copy_longcode','')
    let code = jQuery('.'+currentUid+'-block-modal-longcode').text();
    jQuery(this).closest('#'+currentUid+'_block_modal').first().modal('hide');

    editProps.setAttributes({content: code, content_preview: code})

});
	jQuery(document).on('click','.'+uid+'-spotlightr-group-row', function () {
                                    if (jQuery(this).hasClass('selected')) {
                                        jQuery(this).removeClass('selected').siblings('table').remove();
                                    } else {
                                        let list_of_videos;
                                        let url_for_video_list = rendererPath('spotlightr/video-block-get-videos', {id: jQuery(this).data('id')})
                                        wp.apiFetch({
                                            path: url_for_video_list,
                                            method: 'POST',
                                        }).then((fetchResponse) => {
                                            let video_data = JSON.parse(fetchResponse.rendered)
                                            list_of_videos = ''+
                                                video_data.videos.map(function (item) {
                                                    return  '<table class="spotlightr-video-row">' +
                                                        '       <tr>' +
                                                        '           <td class="spotlightr-videolist-thumbnail"><img src="'+item.thumbnail+'" height="40"></td>' +
                                                        '           <td class="spotlightr-videolist-title"><span>'+item.name+'</span></td>' +
                                                        '           <td class="spotlightr-videolist-plays">' +
                                                        '           </td>' +
                                                        '           <td class="spotlightr-videolist-actions">' +
                                                        '               <span class="spotlightr-video-publish spotlightr-block-publish" data-uid="'+uid+'" data-id="' + item.id + '" data-subdomain="' + video_data.subdomain + '"' +
                                                        '               data-resolution="' + item.customResolution + '" data-encode-id="' + btoa(item.id) + '"' +
                                                        '               ><i class="dashicons dashicons-admin-links"></i></span>' +
                                                        '           </td>' +
                                                        '       </tr>' +
                                                        '     </table>'}).join('');
                                            jQuery(this).addClass('selected').after(list_of_videos);
                                        })
                                    }
                                });



const videoIcon = videoBlockElement('img', {src: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAASlQTFRFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVCeTqwAAAGN0Uk5TAARbNab//jABQSn67X0SNvWT/OJFDdqi83qJVR7hbJwDe+Zw2QwF1dhI+1lE/bsjt7LykTfT9n5OgxGw+egJyuSPCPTFWFxXBzoYaTG6ghVTBkKY40buznIf7JVm8SQih8IaQwx6HQAAAPhJREFUeJxt0OdWwkAQBeALcmMEJKLGXgDFgiJiF1CkWlDB3uv7PwQbICHJZn7smTPf7s6ZAQCffwCeEaASGFSNbCjogBDJ8HAE0EaiDhilEWPj+gQnPWBKmyZnZAjNzs2TCzIsxuIKmVjSXbCcXFnt/Li2rtohtYHNNLuxlbHBdgxZ7uzuRfaFHBz24Qg49udEnjfeFCw4OTVfFw04s6Bk1ssVUqlqFtRMqIvr5xf95pfivGpcAzfh26Z9jtbd/cNj+gmZ3pYtSD2Lli86co6N9JbIV7ijC5U3Cd478CHV8ZkQ+/v6lgHJHyq/HnUxA//KnqDW/92lNmqHGSP6I/X3AAAAAElFTkSuQmCC'});

wp.blocks.registerBlockType('spotlightr/video',
    {
        title: 'Spotlightr Video',
        icon: videoIcon,
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
            uid: {
                type: 'html',
                default: ''
            },
        },
        edit: (props) => {
			editProps = props
            let content_block = props.attributes.content===''?
                videoBlockElement(wp.element.RawHTML,{}, props.attributes.content_preview)
                :
                    videoBlockElement(wp.components.SandBox,{
                        html:props.attributes.content});

            let url = rendererPath('spotlightr/video-block')
            if(!props.attributes.is_loading) {
                props.setAttributes({content_preview: 'Loading', is_loading: true})
                wp.apiFetch({
                    path: url,
                    method: 'POST',
                }).then((fetchResponse) => {
                    if (fetchResponse) {
                        let data = JSON.parse(fetchResponse.rendered)
                        if (data.error_message === true){
                            let content = '<div><p>You need to authorize to<a href="'+data.error_url+'" target="_blank"> Spotlightr plugin</a></p></div>'
                            props.setAttributes({content_preview: content, is_loading: true})
							editProps = props
                        } else {
                            let content = createTemplate(data);
                            props.setAttributes({content_preview: content, is_loading: true})
                            document.dispatchEvent(new CustomEvent("bind_spotlightr",{uid:uid}));
							editProps = props




                        }
                    }
                })
            }

            return   content_block;
        },
        save:  (props)=> {

            return  videoBlockElement(wp.element.RawHTML,{}, props.attributes.content);
        },

    }
)
