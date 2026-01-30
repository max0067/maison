/**
 * SEO Internal Linking - Admin JavaScript
 */

(function($) {
    'use strict';

    var SIL = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Bulk analyze
            $('#sil-bulk-analyze').on('click', this.bulkAnalyze.bind(this));

            // Scan existing links
            $('#sil-scan-links').on('click', this.scanLinks.bind(this));

            // Analyze single post
            $('.sil-analyze-post').on('click', this.analyzePost.bind(this));

            // Insert link
            $(document).on('click', '.sil-insert-link', this.insertLink.bind(this));

            // Dismiss suggestion
            $(document).on('click', '.sil-dismiss-suggestion', this.dismissSuggestion.bind(this));

            // Export CSV
            $('#sil-export-csv').on('click', this.exportCSV.bind(this));
        },

        /**
         * Bulk analyze all posts
         */
        bulkAnalyze: function(e) {
            e.preventDefault();

            var $button = $('#sil-bulk-analyze');
            var $progress = $('#sil-progress-bar');
            var $progressInner = $progress.find('.sil-progress-inner');
            var $progressText = $progress.find('.sil-progress-text');

            $button.prop('disabled', true);
            $progress.show();

            this.runBulkAnalysis(0, $progressInner, $progressText, function() {
                $button.prop('disabled', false);
                $progressText.text(silAdmin.strings.analyzed);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            });
        },

        /**
         * Run bulk analysis recursively
         */
        runBulkAnalysis: function(offset, $progressInner, $progressText, callback) {
            var self = this;

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_bulk_analyze',
                    nonce: silAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            $progressInner.css('width', '100%');
                            callback();
                        } else {
                            $progressInner.css('width', response.data.progress + '%');
                            $progressText.text(response.data.message);
                            self.runBulkAnalysis(response.data.offset, $progressInner, $progressText, callback);
                        }
                    } else {
                        alert(silAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(silAdmin.strings.error);
                }
            });
        },

        /**
         * Scan existing links
         */
        scanLinks: function(e) {
            e.preventDefault();

            var $button = $('#sil-scan-links');
            var $progress = $('#sil-progress-bar');
            var $progressInner = $progress.find('.sil-progress-inner');
            var $progressText = $progress.find('.sil-progress-text');

            $button.prop('disabled', true);
            $progress.show();
            $progressText.text(silAdmin.strings.scanning);

            this.runLinkScan(0, $progressInner, $progressText, function() {
                $button.prop('disabled', false);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            });
        },

        /**
         * Run link scan recursively
         */
        runLinkScan: function(offset, $progressInner, $progressText, callback) {
            var self = this;

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_scan_links',
                    nonce: silAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            $progressInner.css('width', '100%');
                            $progressText.text(response.data.message);
                            callback();
                        } else {
                            $progressInner.css('width', response.data.progress + '%');
                            $progressText.text(response.data.message);
                            self.runLinkScan(response.data.offset, $progressInner, $progressText, callback);
                        }
                    } else {
                        alert(silAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(silAdmin.strings.error);
                }
            });
        },

        /**
         * Analyze single post
         */
        analyzePost: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var postId = $button.data('post-id');
            var $result = $button.siblings('.sil-metabox-result');

            $button.prop('disabled', true).text(silAdmin.strings.analyzing);

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_analyze_post',
                    nonce: silAdmin.nonce,
                    post_id: postId
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Analyser cet article');

                    if (response.success) {
                        $result.html('<p style="color: green;">' + response.data.message + '</p>').show();

                        if (response.data.keywords && response.data.keywords.length > 0) {
                            var keywords = response.data.keywords.slice(0, 5).map(function(kw) {
                                return kw.keyword;
                            }).join(', ');
                            $result.append('<p><strong>Mots-clés:</strong> ' + keywords + '...</p>');
                        }
                    } else {
                        $result.html('<p style="color: red;">' + response.data + '</p>').show();
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Analyser cet article');
                    alert(silAdmin.strings.error);
                }
            });
        },

        /**
         * Insert link
         */
        insertLink: function(e) {
            e.preventDefault();

            if (!confirm(silAdmin.strings.confirm_insert)) {
                return;
            }

            var $button = $(e.currentTarget);
            var $row = $button.closest('tr');
            var sourceId = $button.data('source');
            var targetId = $button.data('target');
            var anchor = $button.data('anchor');
            var suggestionId = $button.data('suggestion');

            $button.prop('disabled', true);

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_insert_link',
                    nonce: silAdmin.nonce,
                    source_id: sourceId,
                    target_id: targetId,
                    anchor: anchor,
                    suggestion_id: suggestionId
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('sil-row-success');
                        $row.find('.sil-status').removeClass('sil-status-pending').addClass('sil-status-applied').text('Appliqué');
                        $button.closest('td').html('<span class="dashicons dashicons-yes-alt" style="color: green;"></span>');
                    } else {
                        $button.prop('disabled', false);
                        alert(response.data);
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    alert(silAdmin.strings.error);
                }
            });
        },

        /**
         * Dismiss suggestion
         */
        dismissSuggestion: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $row = $button.closest('tr');
            var suggestionId = $button.data('suggestion');

            $button.prop('disabled', true);

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_dismiss_suggestion',
                    nonce: silAdmin.nonce,
                    suggestion_id: suggestionId
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('sil-row-dismissed');
                        $row.find('.sil-status').removeClass('sil-status-pending').addClass('sil-status-rejected').text('Ignoré');
                        $row.find('.sil-insert-link, .sil-dismiss-suggestion').remove();
                    } else {
                        $button.prop('disabled', false);
                        alert(response.data);
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    alert(silAdmin.strings.error);
                }
            });
        },

        /**
         * Export CSV
         */
        exportCSV: function(e) {
            e.preventDefault();

            var $button = $('#sil-export-csv');
            $button.prop('disabled', true);

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_export_csv',
                    nonce: silAdmin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false);

                    if (response.success) {
                        // Create and download CSV file
                        var blob = new Blob([response.data.csv], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement('a');
                        var url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', 'seo-internal-linking-stats.csv');
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert(silAdmin.strings.error);
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    alert(silAdmin.strings.error);
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SIL.init();
    });

})(jQuery);
