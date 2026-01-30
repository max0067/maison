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
            var self = this;

            // Bulk analyze
            $(document).on('click', '#sil-bulk-analyze', this.bulkAnalyze.bind(this));

            // Scan existing links
            $(document).on('click', '#sil-scan-links', this.scanLinks.bind(this));

            // Analyze single post
            $(document).on('click', '.sil-analyze-post', this.analyzePost.bind(this));

            // Insert link
            $(document).on('click', '.sil-insert-link', this.insertLink.bind(this));

            // Dismiss suggestion
            $(document).on('click', '.sil-dismiss-suggestion', this.dismissSuggestion.bind(this));

            // Export CSV
            $(document).on('click', '#sil-export-csv', this.exportCSV.bind(this));

            // Selection multiple - Tout sélectionner (utiliser délégation)
            $(document).on('change', '#sil-select-all', function() {
                self.toggleSelectAll($(this).prop('checked'));
            });
            $(document).on('change', '#sil-select-all-header', function() {
                self.toggleSelectAll($(this).prop('checked'));
            });

            // Checkbox individuel
            $(document).on('change', '.sil-select-item', function() {
                self.updateSelectedCount();
            });

            // Bulk actions
            $(document).on('click', '#sil-bulk-insert', this.bulkInsert.bind(this));
            $(document).on('click', '#sil-bulk-dismiss', this.bulkDismiss.bind(this));

            // Modifier l'ancre
            $(document).on('click', '.sil-edit-anchor', this.editAnchor.bind(this));
            $(document).on('click', '.sil-save-anchor', this.saveAnchor.bind(this));
            $(document).on('click', '.sil-cancel-anchor', this.cancelAnchor.bind(this));

            // Filtres
            $(document).on('change', '#sil-filter-status, #sil-filter-score', this.applyFilters.bind(this));
            $(document).on('input', '#sil-filter-search', this.applyFilters.bind(this));
        },

        /**
         * Toggle select all
         */
        toggleSelectAll: function(checked) {
            // Synchroniser les deux checkboxes
            $('#sil-select-all, #sil-select-all-header').prop('checked', checked);

            // Sélectionner toutes les lignes visibles (respecter les filtres)
            $('#sil-suggestions-table tbody tr:visible .sil-select-item').prop('checked', checked);

            this.updateSelectedCount();
        },

        /**
         * Update selected count
         */
        updateSelectedCount: function() {
            var count = $('.sil-select-item:checked').length;
            var total = $('.sil-select-item').length;

            $('#sil-selected-num').text(count);

            // Enable/disable bulk action buttons
            var hasSelection = count > 0;
            $('#sil-bulk-insert, #sil-bulk-dismiss').prop('disabled', !hasSelection);

            // Mettre à jour l'état du "tout sélectionner"
            var allChecked = count === total && total > 0;
            $('#sil-select-all, #sil-select-all-header').prop('checked', allChecked);
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
         * Edit anchor text
         */
        editAnchor: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $row = $btn.closest('tr');
            var $anchorCell = $row.find('.sil-anchor-cell');
            var currentAnchor = $row.data('anchor');

            $anchorCell.html(
                '<input type="text" class="sil-anchor-input" value="' + currentAnchor + '" style="width: 100%;">' +
                '<button type="button" class="button button-small sil-save-anchor" title="Enregistrer">&#10004;</button>' +
                '<button type="button" class="button button-small sil-cancel-anchor" title="Annuler">&#10008;</button>'
            );
            $anchorCell.find('input').focus().select();
        },

        /**
         * Save edited anchor
         */
        saveAnchor: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $row = $btn.closest('tr');
            var $anchorCell = $row.find('.sil-anchor-cell');
            var newAnchor = $anchorCell.find('.sil-anchor-input').val().trim();

            if (newAnchor) {
                $row.data('anchor', newAnchor);
                $row.find('.sil-insert-link').data('anchor', newAnchor);
                $anchorCell.html(
                    '<code>' + newAnchor + '</code> ' +
                    '<button type="button" class="button button-small sil-edit-anchor" title="Modifier">&#9998;</button>'
                );
            }
        },

        /**
         * Cancel anchor edit
         */
        cancelAnchor: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $row = $btn.closest('tr');
            var $anchorCell = $row.find('.sil-anchor-cell');
            var originalAnchor = $row.data('anchor');

            $anchorCell.html(
                '<code>' + originalAnchor + '</code> ' +
                '<button type="button" class="button button-small sil-edit-anchor" title="Modifier">&#9998;</button>'
            );
        },

        /**
         * Apply filters
         */
        applyFilters: function() {
            var statusFilter = $('#sil-filter-status').val();
            var scoreFilter = $('#sil-filter-score').val();
            var searchFilter = $('#sil-filter-search').val().toLowerCase();

            $('#sil-suggestions-table tbody tr').each(function() {
                var $row = $(this);
                var status = $row.data('status');
                var score = parseFloat($row.find('.sil-score').text()) || 0;
                var sourceText = $row.find('td:eq(1)').text().toLowerCase();
                var targetText = $row.find('td:eq(2)').text().toLowerCase();
                var anchorText = $row.data('anchor').toLowerCase();

                var showStatus = !statusFilter || status === statusFilter;
                var showScore = true;
                var showSearch = true;

                // Filtre par score
                if (scoreFilter === 'high') {
                    showScore = score >= 70;
                } else if (scoreFilter === 'medium') {
                    showScore = score >= 40 && score < 70;
                } else if (scoreFilter === 'low') {
                    showScore = score < 40;
                }

                // Filtre par recherche
                if (searchFilter) {
                    showSearch = sourceText.indexOf(searchFilter) !== -1 ||
                                 targetText.indexOf(searchFilter) !== -1 ||
                                 anchorText.indexOf(searchFilter) !== -1;
                }

                if (showStatus && showScore && showSearch) {
                    $row.show();
                } else {
                    $row.hide();
                    $row.find('.sil-select-item').prop('checked', false);
                }
            });

            this.updateSelectedCount();
            this.updateFilteredCount();
        },

        /**
         * Update filtered count
         */
        updateFilteredCount: function() {
            var visible = $('#sil-suggestions-table tbody tr:visible').length;
            var total = $('#sil-suggestions-table tbody tr').length;

            if (visible < total) {
                $('#sil-filtered-info').text('(' + visible + ' / ' + total + ' affichés)').show();
            } else {
                $('#sil-filtered-info').hide();
            }
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
                                $row.find('.sil-status').html('<span style="color: orange;" title="' + (detail.result.message || 'Erreur') + '">Échec</span>');
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
            $row.find('.sil-insert-link, .sil-dismiss-suggestion, .sil-edit-anchor').remove();
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
            $row.find('.sil-insert-link, .sil-dismiss-suggestion, .sil-edit-anchor').remove();
            $row.find('td:last').html('<span class="dashicons dashicons-dismiss" style="color: #999;"></span>');
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
            var anchor = $row.data('anchor'); // Utiliser l'ancre de la row (peut avoir été modifiée)
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

    /**
     * Générateur d'articles IA
     */
    var SIL_Generator = {
        keyword: '',
        title: '',
        content: '',
        metaDescription: '',
        imageUrl: '',

        init: function() {
            this.bindEvents();
            this.setupCharCounters();
        },

        bindEvents: function() {
            var self = this;
            console.log('SIL Generator: Binding events...');

            // Générer l'article
            $(document).on('click', '#sil-generate-article', function(e) {
                e.preventDefault();
                console.log('SIL Generator: Button clicked');
                self.generateArticle();
            });

            // Régénérer
            $(document).on('click', '#sil-regenerate', function() {
                self.generateArticle();
            });

            // Générer l'image
            $(document).on('click', '#sil-generate-image', function() {
                self.generateImage();
            });

            // Régénérer l'image
            $(document).on('click', '#sil-regenerate-image', function() {
                self.generateImage();
            });

            // Passer l'image
            $(document).on('click', '#sil-skip-image', function() {
                self.imageUrl = '';
                self.showPublishStep();
            });

            // Créer l'article
            $(document).on('click', '#sil-create-post', function() {
                self.createPost();
            });

            // Nouveau article
            $(document).on('click', '#sil-new-article', function() {
                self.reset();
            });

            // Enter sur le champ mot-clé
            $(document).on('keypress', '#sil-keyword', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.generateArticle();
                }
            });
        },

        setupCharCounters: function() {
            // Compteur titre
            $(document).on('input', '#sil-preview-title', function() {
                var len = $(this).val().length;
                var $counter = $('#sil-title-count');
                $counter.text(len + '/70');
                $counter.removeClass('warning error');
                if (len > 70) $counter.addClass('error');
                else if (len > 60) $counter.addClass('warning');
            });

            // Compteur meta
            $(document).on('input', '#sil-preview-meta', function() {
                var len = $(this).val().length;
                var $counter = $('#sil-meta-count');
                $counter.text(len + '/154');
                $counter.removeClass('warning error');
                if (len > 154) $counter.addClass('error');
                else if (len > 145) $counter.addClass('warning');
            });

            // Compteur mots
            $(document).on('input', '#sil-preview-content', function() {
                var text = $(this).val().replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                var words = text ? text.split(' ').length : 0;
                $('#sil-word-count span').text(words);
            });
        },

        generateArticle: function() {
            var self = this;
            console.log('SIL Generator: generateArticle called');

            var keyword = $('#sil-keyword').val().trim();
            console.log('SIL Generator: keyword =', keyword);

            if (!keyword) {
                alert('Veuillez entrer un mot-clé.');
                return;
            }

            this.keyword = keyword;

            var $btn = $('#sil-generate-article, #sil-regenerate');
            var $status = $('#sil-generate-status');

            console.log('SIL Generator: silAdmin =', typeof silAdmin !== 'undefined' ? 'defined' : 'undefined');

            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active"></span> Génération en cours... (peut prendre 30-60 secondes)');

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_generate_article',
                    nonce: silAdmin.nonce,
                    keyword: keyword
                },
                timeout: 180000, // 3 minutes
                success: function(response) {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        self.title = response.data.title || '';
                        self.content = response.data.content || '';
                        self.metaDescription = response.data.meta_description || '';

                        // Remplir les champs
                        $('#sil-preview-title').val(self.title).trigger('input');
                        $('#sil-preview-meta').val(self.metaDescription).trigger('input');
                        $('#sil-preview-content').val(self.content).trigger('input');

                        // Afficher l'étape preview
                        $('#sil-step-preview').show();
                        $status.html('<span style="color: green;">&#10004; Article généré !</span>');

                        // Scroll vers preview
                        $('html, body').animate({
                            scrollTop: $('#sil-step-preview').offset().top - 50
                        }, 500);
                    } else {
                        $status.html('<span style="color: red;">Erreur: ' + response.data + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    var message = 'Erreur de connexion.';
                    if (status === 'timeout') {
                        message = 'Délai d\'attente dépassé. Réessayez.';
                    }
                    $status.html('<span style="color: red;">' + message + '</span>');
                }
            });
        },

        generateImage: function() {
            var self = this;

            var $btn = $('#sil-generate-image, #sil-regenerate-image');
            var $status = $('#sil-image-status');

            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active"></span> Génération de l\'image... (30-60 secondes)');

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_generate_image',
                    nonce: silAdmin.nonce,
                    keyword: this.keyword,
                    title: $('#sil-preview-title').val()
                },
                timeout: 120000,
                success: function(response) {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        self.imageUrl = response.data.image_url;

                        // Afficher l'image
                        $('#sil-image-preview').html('<img src="' + self.imageUrl + '" alt="Image générée">');

                        // Afficher les étapes suivantes
                        $('#sil-step-image').show();
                        self.showPublishStep();

                        $status.html('<span style="color: green;">&#10004; Image générée !</span>');

                        // Scroll
                        $('html, body').animate({
                            scrollTop: $('#sil-step-image').offset().top - 50
                        }, 500);
                    } else {
                        $status.html('<span style="color: red;">Erreur: ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.html('<span style="color: red;">Erreur de connexion.</span>');
                }
            });
        },

        showPublishStep: function() {
            $('#sil-step-publish').show();
            $('html, body').animate({
                scrollTop: $('#sil-step-publish').offset().top - 50
            }, 500);
        },

        createPost: function() {
            var self = this;

            var $btn = $('#sil-create-post');
            var $status = $('#sil-publish-status');

            // Récupérer les valeurs modifiées
            var title = $('#sil-preview-title').val();
            var content = $('#sil-preview-content').val();
            var metaDescription = $('#sil-preview-meta').val();
            var status = $('input[name="sil-publish-status"]:checked').val();

            if (!title || !content) {
                alert('Titre et contenu requis.');
                return;
            }

            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active"></span> Création de l\'article...');

            $.ajax({
                url: silAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sil_create_post',
                    nonce: silAdmin.nonce,
                    title: title,
                    content: content,
                    meta_description: metaDescription,
                    keyword: this.keyword,
                    image_url: this.imageUrl,
                    status: status
                },
                timeout: 60000,
                success: function(response) {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        // Mettre à jour les liens
                        $('#sil-edit-post-link').attr('href', response.data.edit_url);
                        $('#sil-preview-post-link').attr('href', response.data.preview_url);

                        // Afficher le succès
                        $('#sil-step-publish').hide();
                        $('#sil-step-success').show();

                        $status.html('');

                        // Scroll
                        $('html, body').animate({
                            scrollTop: $('#sil-step-success').offset().top - 50
                        }, 500);
                    } else {
                        $status.html('<span style="color: red;">Erreur: ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.html('<span style="color: red;">Erreur de connexion.</span>');
                }
            });
        },

        reset: function() {
            this.keyword = '';
            this.title = '';
            this.content = '';
            this.metaDescription = '';
            this.imageUrl = '';

            $('#sil-keyword').val('');
            $('#sil-preview-title').val('');
            $('#sil-preview-meta').val('');
            $('#sil-preview-content').val('');
            $('#sil-image-preview').html('<p class="description">L\'image générée apparaîtra ici</p>');

            $('#sil-step-preview, #sil-step-image, #sil-step-publish, #sil-step-success').hide();
            $('#sil-generate-status, #sil-image-status, #sil-publish-status').html('');

            $('html, body').animate({
                scrollTop: $('#sil-step-keyword').offset().top - 50
            }, 500);
        }
    };

    // Initialize Generator when DOM is ready
    $(document).ready(function() {
        console.log('SIL Admin JS loaded, hook check passed');

        if ($('#sil-keyword').length) {
            console.log('SIL Generator: Found #sil-keyword element');
            console.log('SIL Generator: silAdmin available:', typeof silAdmin !== 'undefined');
            SIL_Generator.init();
            console.log('SIL Generator: Initialized successfully');

            // Fallback direct binding
            $('#sil-generate-article').on('click', function(e) {
                e.preventDefault();
                console.log('SIL Generator: Direct click handler fired');
                SIL_Generator.generateArticle();
            });
        }
    });

})(jQuery);
