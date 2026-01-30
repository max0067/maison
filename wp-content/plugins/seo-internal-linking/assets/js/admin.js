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
            this.updateSelectedCount();
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

            // Selection multiple
            $('#sil-select-all, #sil-select-all-header').on('change', this.toggleSelectAll.bind(this));
            $(document).on('change', '.sil-select-item', this.updateSelectedCount.bind(this));

            // Bulk actions
            $('#sil-bulk-insert').on('click', this.bulkInsert.bind(this));
            $('#sil-bulk-dismiss').on('click', this.bulkDismiss.bind(this));
        },

        /**
         * Toggle select all
         */
        toggleSelectAll: function(e) {
            var checked = $(e.currentTarget).prop('checked');
            $('#sil-select-all, #sil-select-all-header').prop('checked', checked);
            $('.sil-select-item').prop('checked', checked);
            this.updateSelectedCount();
        },

        /**
         * Update selected count
         */
        updateSelectedCount: function() {
            var count = $('.sil-select-item:checked').length;
            $('#sil-selected-num').text(count);

            // Enable/disable bulk action buttons
            $('#sil-bulk-insert, #sil-bulk-dismiss').prop('disabled', count === 0);
        },

        /**
         * Get selected items data
         */
        getSelectedItems: function() {
            var items = [];
            $('.sil-select-item:checked').each(function() {
                var $row = $(this).closest('tr');
                items.push({
                    suggestion_id: $row.data('suggestion-id'),
                    source_id: $row.data('source'),
                    target_id: $row.data('target'),
                    anchor: $row.data('anchor'),
                    $row: $row
                });
            });
            return items;
        },

        /**
         * Bulk insert links
         */
        bulkInsert: function(e) {
            e.preventDefault();

            var items = this.getSelectedItems();
            if (items.length === 0) {
                alert('Aucune suggestion sélectionnée.');
                return;
            }

            if (!confirm('Voulez-vous insérer ' + items.length + ' lien(s) ?')) {
                return;
            }

            var $button = $('#sil-bulk-insert');
            var $status = $('#sil-bulk-status');
            var self = this;

            $button.prop('disabled', true);
            $status.html('<span class="spinner is-active" style="float: none;"></span> Insertion en cours...');

            // Marquer les lignes en cours de traitement
            items.forEach(function(item) {
                item.$row.addClass('sil-processing');
            });

            // Préparer les données
            var linksData = items.map(function(item) {
                return {
                    source_id: item.source_id,
                    target_id: item.target_id,
                    anchor: item.anchor,
                    suggestion_id: item.suggestion_id
                };
            });

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_bulk_insert_links',
                    nonce: silAdmin.nonce,
                    links: linksData
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">' + response.data.message + '</span>');

                        // Mettre à jour l'interface
                        response.data.details.forEach(function(detail, index) {
                            var $row = items[index].$row;
                            $row.removeClass('sil-processing');

                            if (detail.result.success) {
                                self.markRowAsApplied($row);
                            } else {
                                $row.find('.sil-status').html('<span style="color: orange;">Échec</span>');
                            }
                        });

                        self.updateSelectedCount();
                    } else {
                        $status.html('<span style="color: red;">' + response.data + '</span>');
                        items.forEach(function(item) {
                            item.$row.removeClass('sil-processing');
                        });
                    }
                    $button.prop('disabled', false);
                },
                error: function() {
                    $status.html('<span style="color: red;">Erreur de connexion.</span>');
                    items.forEach(function(item) {
                        item.$row.removeClass('sil-processing');
                    });
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Bulk dismiss suggestions
         */
        bulkDismiss: function(e) {
            e.preventDefault();

            var items = this.getSelectedItems();
            if (items.length === 0) {
                alert('Aucune suggestion sélectionnée.');
                return;
            }

            if (!confirm('Voulez-vous ignorer ' + items.length + ' suggestion(s) ?')) {
                return;
            }

            var $button = $('#sil-bulk-dismiss');
            var $status = $('#sil-bulk-status');
            var self = this;
            var completed = 0;
            var total = items.length;

            $button.prop('disabled', true);
            $status.text('Traitement en cours... 0/' + total);

            // Traiter les éléments un par un
            items.forEach(function(item) {
                $.ajax({
                    url: silAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sil_dismiss_suggestion',
                        nonce: silAdmin.nonce,
                        suggestion_id: item.suggestion_id
                    },
                    success: function(response) {
                        completed++;
                        $status.text('Traitement en cours... ' + completed + '/' + total);

                        if (response.success) {
                            self.markRowAsDismissed(item.$row);
                        }

                        if (completed === total) {
                            $status.html('<span style="color: green;">' + total + ' suggestion(s) ignorée(s).</span>');
                            self.updateSelectedCount();
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === total) {
                            $button.prop('disabled', false);
                        }
                    }
                });
            });
        },

        /**
         * Mark row as applied
         */
        markRowAsApplied: function($row) {
            $row.addClass('sil-row-success');
            $row.find('.sil-status').removeClass('sil-status-pending').addClass('sil-status-applied').text('Appliqué');
            $row.find('.sil-select-item').remove();
            $row.find('td:last').html('<span class="dashicons dashicons-yes-alt" style="color: green;"></span>');
            $row.data('status', 'applied');
        },

        /**
         * Mark row as dismissed
         */
        markRowAsDismissed: function($row) {
            $row.addClass('sil-row-dismissed');
            $row.find('.sil-status').removeClass('sil-status-pending').addClass('sil-status-rejected').text('Ignoré');
            $row.find('.sil-select-item').remove();
            $row.find('.sil-insert-link, .sil-dismiss-suggestion').remove();
            $row.data('status', 'rejected');
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

            var $button = $(e.currentTarget);
            var $row = $button.closest('tr');
            var sourceId = $button.data('source');
            var targetId = $button.data('target');
            var anchor = $button.data('anchor');
            var suggestionId = $button.data('suggestion');
            var self = this;

            $button.prop('disabled', true).text('...');

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
                        self.markRowAsApplied($row);
                    } else {
                        $button.prop('disabled', false).text('Insérer');
                        alert(response.data || 'Erreur lors de l\'insertion.');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Insérer');
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
            var self = this;

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
                        self.markRowAsDismissed($row);
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
