/**
 * Filtre « Dossier » dans la vue Grille de la médiathèque + la modale wp.media.
 *
 * WordPress n'expose le hook PHP `restrict_manage_posts` que sur la vue Liste.
 * Pour la vue Grille (par défaut) et la modale wp.media (sélecteur de featured
 * image, bloc gallery, etc.), on ajoute un select Backbone qui pousse le filtre
 * `oli_media_folder` dans `query.props`, ce qui est intercepté côté PHP par
 * `MediaFoldersAdmin::filterAjaxAttachments`.
 *
 * @global oliMediaFolders [{slug, name, parent, depth}]
 */
(function () {
    if (typeof wp === 'undefined' || !wp.media) {
        return;
    }
    const FOLDERS = (typeof oliMediaFolders === 'object' && Array.isArray(oliMediaFolders)) ? oliMediaFolders : [];
    if (FOLDERS.length === 0) {
        return;
    }

    // Filtre Backbone qui apparaît dans la barre d'outils des AttachmentsBrowser.
    const FolderFilter = wp.media.view.AttachmentFilters.extend({
        id: 'oli-media-folder-filter',
        className: 'attachment-filters',
        createFilters: function () {
            const filters = {
                all: { text: 'Tous les dossiers', props: { oli_media_folder: '' }, priority: 1 },
            };
            FOLDERS.forEach((f) => {
                const prefix = (f.depth && f.depth > 0) ? '— '.repeat(f.depth) + ' ' : '';
                filters[f.slug] = {
                    text: prefix + f.name,
                    props: { oli_media_folder: f.slug },
                    priority: 10 + (f.depth || 0),
                };
            });
            this.filters = filters;
        },
    });

    // Patch la création de la toolbar pour y injecter notre filtre.
    const origCreateToolbar = wp.media.view.AttachmentsBrowser.prototype.createToolbar;
    wp.media.view.AttachmentsBrowser.prototype.createToolbar = function () {
        origCreateToolbar.apply(this, arguments);
        this.toolbar.set('oliMediaFolderFilter', new FolderFilter({
            controller: this.controller,
            model: this.collection.props,
            priority: -75,
        }).render());
    };

    // Assure que le terme par défaut (`all` → vide) reset bien le filtre.
    const origGetQueryParams = wp.media.view.AttachmentFilters.prototype.select;
    if (origGetQueryParams) {
        wp.media.view.AttachmentFilters.prototype.select = function () {
            origGetQueryParams.apply(this, arguments);
        };
    }
})();
