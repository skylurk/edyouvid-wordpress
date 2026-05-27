let videoBlockElementPlaylist = wp.element.createElement;
let uid_p = "uid" + Math.random().toString(16).slice(2)

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

function createTemplatePlaylist(data){

    let template= '<div id="'+uid_p+'">' +
        '<div>' +
        '    <table id="spotlightr_playlist_table">' +
        '        <thead>' +
        '        <tr>' +
        '            <th class="spotlightr-playlist-thumbnail"></th>' +
        '            <th>Title</th>' +
        '            <th># of videos</th>' +
        '            <th class="spotlightr-playlist-actions">Actions</th>' +
        '        </tr>' +
        '        </thead>' +
        Object.keys(data.playlists).map(function (key) {
            let value = data.playlists[key]
            return '<tr>' +
            '        <td>' +
            '                <img src="'+ value.data['thumbnail'] +'" height="40">' +
            '        </td>' +
            '        <td>' +
            '            <span class="spotlightr-playlist-title">'+value.name+'</span>' +
            '        </td>' +
            '        <td>' +
            '            <span class="spotlightr-playlist-video-count">'+value.data['videos'].length+'</span>' +
            '        </td>' +
            '        <td>' +
            '            <span class="spotlightr-playlist-publish spotlightr-block-publish" id="'+uid_p+'_spotlightr_block_publish" ' +
            '                  data-encode-id="'+btoa(value.id)+'"' +
            '                  data-subdomain="' + data.subdomain + '" data-toggle="modal" data-target="#'+uid_p+'_block_modal"><i' +
            '                class="dashicons dashicons-admin-links"></i></span>' +
            '        </td>' +
            '    </tr>'}).join('')+
            '</table>'+
        '    <div class="modal spotlightr-modal-fade" id="'+uid_p+'_block_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">' +
        '        <div class="modal-dialog spotlightr-modal-dialog" role="document">' +
        '            <div class="modal-content spotlightr-modal-content">' +
        '                <div class="modal-header">' +
        '                    <h4 class="modal-title" id="'+uid_p+'_myModalLabel">Publish Options</h4>' +
        '                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span' +
        '                            aria-hidden="true">&times;</span></button>' +
        '                </div>' +
        '                <div class="modal-body spotlightr-modal-body spotlightr-block-modal-body">' +
        '                    <div class="spotlightr-modal-longcode-wraper" style="display: none">' +
        '                        <h5>Longcode</h5>' +
        '                        <textarea class="'+uid_p+'-block-modal-longcode" id="'+uid_p+'_block_modal_longcode"><script src="https://subdomain_name.cdn.spotlightr.com/assets/spotlightr.js"></script><iframe ' +
        'allow="autoplay" class="video-player-container spotlightr" data-playerid="encode_id" allowtransparency="true" style="max-width:100%" ' +
        'name="videoPlayerframe" allowfullscreen="true" src="https://subdomain_name.cdn.spotlightr.com/watch/playlist/encode_id?aspect" ' +
        'watch-type="" url-params="aspect" frameborder="0" scrolling="no"></iframe></textarea>' +
        '                    </div>' +
        '                       <h3>There are no additional options for playlist</h3>' +
        '                    <button id="'+uid_p+'_copy_longcode" class="spotlightr-copy spotlightr-button">Publish Playlist</button>' +
        '                </div>' +
        '            </div>' +
        '        </div>' +
        '</div>'

    return template;
}


const playlistIcon = videoBlockElementPlaylist('img', {src: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAASlQTFRFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVCeTqwAAAGN0Uk5TAARbNab//jABQSn67X0SNvWT/OJFDdqi83qJVR7hbJwDe+Zw2QwF1dhI+1lE/bsjt7LykTfT9n5OgxGw+egJyuSPCPTFWFxXBzoYaTG6ghVTBkKY40buznIf7JVm8SQih8IaQwx6HQAAAPhJREFUeJxt0OdWwkAQBeALcmMEJKLGXgDFgiJiF1CkWlDB3uv7PwQbICHJZn7smTPf7s6ZAQCffwCeEaASGFSNbCjogBDJ8HAE0EaiDhilEWPj+gQnPWBKmyZnZAjNzs2TCzIsxuIKmVjSXbCcXFnt/Li2rtohtYHNNLuxlbHBdgxZ7uzuRfaFHBz24Qg49udEnjfeFCw4OTVfFw04s6Bk1ssVUqlqFtRMqIvr5xf95pfivGpcAzfh26Z9jtbd/cNj+gmZ3pYtSD2Lli86co6N9JbIV7ijC5U3Cd478CHV8ZkQ+/v6lgHJHyq/HnUxA//KnqDW/92lNmqHGSP6I/X3AAAAAElFTkSuQmCC'});

wp.blocks.registerBlockType('spotlightr/playlist',
    {
        title: 'Spotlightr Playlist',
        icon: playlistIcon,
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
                videoBlockElementPlaylist(wp.element.RawHTML,{}, props.attributes.content_preview)
                :
                videoBlockElementPlaylist(wp.components.SandBox,{
                    html:props.attributes.content});

            let url_p = rendererPath('spotlightr/playlist-block')
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
                            let content_p = createTemplatePlaylist(data);
                            props.setAttributes({content_preview: content_p, is_loading: true})
                            document.dispatchEvent(new CustomEvent("bind_spotlightr_playlist",{uid_p:uid_p}));

                            jQuery(document).on('click', '#'+uid_p+'_copy_longcode', function (event) {
                                let code_p = jQuery('#'+uid_p+'_block_modal_longcode').text();
                                jQuery(this).closest('#'+uid_p+'_block_modal').first().modal('hide');
                                props.setAttributes({content: code_p, content_preview: code_p})
                            });
                        }
                    }
                })
            }

            return   content_block;
        },
        save:  (props)=> {

            return  videoBlockElementPlaylist(wp.element.RawHTML,{}, props.attributes.content);
        },

    }
)
