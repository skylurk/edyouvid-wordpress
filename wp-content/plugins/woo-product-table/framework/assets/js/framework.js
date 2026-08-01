/**
 * CA Framework JavaScript
 *
 * Handles dismiss, install, activate, popup, and countdown interactions.
 *
 * @package CA_Framework
 * @version 1.1.0
 */

(function ($) {
    'use strict';

    var CAFramework = {

        /**
         * Initialize all event handlers.
         */
        init: function () {
            this.bindDismiss();
            this.bindPluginActions();
            this.bindPopup();
            this.initCountdowns();
        },

        /**
         * Handle dismiss actions for offers, popups, and notices.
         */
        bindDismiss: function () {
            $(document).on('click', '.ca-fw-dismiss', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var dismissId = $btn.data('dismiss-id');
                var dismissType = $btn.data('dismiss-type') || 'permanent';
                var reshowAfter = $btn.data('reshow-after') || 0;
                var reshowUnit = $btn.data('reshow-unit') || 'days';

                $.ajax({
                    url: caFramework.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ca_framework_dismiss',
                        nonce: caFramework.nonce,
                        dismiss_id: dismissId,
                        dismiss_type: dismissType,
                        reshow_after: reshowAfter,
                        reshow_unit: reshowUnit
                    },
                    success: function () {
                        // Fade out the parent offer/notice/popup
                        var $target = $btn.closest('.ca-fw-offer, .ca-fw-notice, .ca-fw-plugins-wrap');
                        if ($target.length) {
                            $target.fadeOut(300, function () {
                                $(this).remove();
                            });
                        }

                        // Close popup overlay
                        var $popup = $btn.closest('.ca-fw-popup-overlay');
                        if ($popup.length) {
                            $popup.fadeOut(300, function () {
                                $(this).remove();
                            });
                        }
                    }
                });
            });
        },

        /**
         * Handle plugin install and activate actions.
         */
        bindPluginActions: function () {
            // Install Plugin
            $(document).on('click', '.ca-fw-btn-install', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var slug = $btn.data('slug');

                if (!slug) return;

                $btn.addClass('ca-fw-btn-loading')
                    .prop('disabled', true)
                    .html('<span class="dashicons dashicons-update"></span> Installing...');

                wp.updates.installPlugin({
                    slug: slug,
                    success: function (response) {
                        $btn.removeClass('ca-fw-btn-loading ca-fw-btn-primary ca-fw-btn-outline')
                            .addClass('ca-fw-btn-activate ca-fw-btn-success')
                            .prop('disabled', false)
                            .data('action', 'activate')
                            .data('path', response.activateUrl ? slug + '/' + slug + '.php' : '')
                            .html('<span class="dashicons dashicons-yes"></span> Activate');

                        // Try to extract the plugin path from activate URL
                        if (response.activateUrl) {
                            var match = response.activateUrl.match(/plugin=([^&]+)/);
                            if (match) {
                                $btn.data('path', decodeURIComponent(match[1]));
                            }
                        }
                    },
                    error: function (error) {
                        $btn.removeClass('ca-fw-btn-loading')
                            .prop('disabled', false)
                            .html('<span class="dashicons dashicons-download"></span> Install');

                        if (error && error.errorMessage) {
                            alert(error.errorMessage);
                        }
                    }
                });
            });

            // Activate Plugin
            $(document).on('click', '.ca-fw-btn-activate', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var path = $btn.data('path');

                if (!path) return;

                //found required page .ca-fw-required-notice 
                var $requiredPage = $(this).closest('.ca-fw-required-notice');

                $btn.addClass('ca-fw-btn-loading')
                    .prop('disabled', true)
                    .html('<span class="dashicons dashicons-update"></span> Activating...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ca_framework_activate_plugin',
                        nonce: caFramework.nonce,
                        plugin_path: path
                    },
                    success: function (response) {
                        if (response.success) {
                            var $card = $btn.closest('.ca-fw-plugin-card');
                            $card.addClass('ca-fw-plugin-activated');
                            $btn.removeClass('ca-fw-btn-loading')
                                .html('<span class="dashicons dashicons-yes-alt"></span> Active')
                                .prop('disabled', true);

                            // Fade out after a short delay
                            setTimeout(function () {
                                $card.fadeOut(400, function () {
                                    $(this).remove();

                                    // If no more plugin cards, remove the entire section
                                    var $grid = $card.closest('.ca-fw-plugins-grid');
                                    if ($grid.length && $grid.children().length === 0) {
                                        $card.closest('.ca-fw-plugins-wrap, .ca-fw-notice').fadeOut(300, function () {
                                            $(this).remove();
                                        });
                                    }
                                });
                            }, 1000);

                            //if required notice then reload this current page
                            if ($requiredPage.length) {
                                location.reload();
                            }
                        } else {
                            $btn.removeClass('ca-fw-btn-loading')
                                .prop('disabled', false)
                                .html('<span class="dashicons dashicons-yes"></span> Activate');

                            if (response.data) {
                                alert(response.data);
                            }
                        }
                    },
                    error: function () {
                        $btn.removeClass('ca-fw-btn-loading')
                            .prop('disabled', false)
                            .html('<span class="dashicons dashicons-yes"></span> Activate');
                    }
                });
            });
        },

        /**
         * Handle popup close via overlay click.
         */
        bindPopup: function () {
            $(document).on('click', '.ca-fw-popup-overlay', function (e) {
                if ($(e.target).hasClass('ca-fw-popup-overlay')) {
                    $(this).find('.ca-fw-popup-close').trigger('click');
                }
            });

            // Close popup with Escape key
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    var $popup = $('.ca-fw-popup-overlay');
                    if ($popup.length) {
                        $popup.find('.ca-fw-popup-close').trigger('click');
                    }
                }
            });
        },

        /**
         * Initialize all countdown timers on the page.
         */
        initCountdowns: function () {
            var self = this;
            $('.ca-fw-countdown').each(function () {
                self.startCountdown($(this));
            });
        },

        /**
         * Start a countdown timer for a single element.
         *
         * @param {jQuery} $el Countdown element with data-end-date attribute.
         */
        startCountdown: function ($el) {
            var endDate = $el.data('end-date');
            if (!endDate) return;

            var endTime = new Date(endDate + 'Z').getTime();

            function updateTimer() {
                var now = new Date().getTime();
                var diff = endTime - now;

                if (diff <= 0) {
                    $el.find('[data-days]').text('00');
                    $el.find('[data-hours]').text('00');
                    $el.find('[data-minutes]').text('00');
                    $el.find('[data-seconds]').text('00');
                    $el.addClass('ca-fw-countdown-expired');
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                $el.find('[data-days]').text(days < 10 ? '0' + days : days);
                $el.find('[data-hours]').text(hours < 10 ? '0' + hours : hours);
                $el.find('[data-minutes]').text(minutes < 10 ? '0' + minutes : minutes);
                $el.find('[data-seconds]').text(seconds < 10 ? '0' + seconds : seconds);

                setTimeout(updateTimer, 1000);
            }

            updateTimer();
        }
    };

    $(document).ready(function () {
        CAFramework.init();
    });

})(jQuery);
