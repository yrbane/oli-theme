[# Page Galerie Photos : layout vignettes-gauche / grande-droite.
   Variables : post (PostEntity), photos (array), hasPhotos (bool),
               folderGalleries (array), hasFolderGalleries (bool),
               galleryDataJson (string), + base. #]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--gallery page--gallery-photos page--[[ post.slug ]]" lang="[[ post.language.code ]]">
        <header class="page__header">
            <h1 class="page__title">[[ post.title ]]</h1>
        </header>

        [% if hasFolderGalleries %]
        <nav class="gallery__filters" data-gallery-filters aria-label="Filtres par dossier">
            <button type="button" class="gallery__filter is-active" data-gallery-filter="all" aria-pressed="true">Tous</button>
            [% for folder in folderGalleries %]
                <button type="button" class="gallery__filter" data-gallery-filter="folder-[[ folder.slug ]]" aria-pressed="false">[[ folder.name ]]</button>
            [% endfor %]
        </nav>
        <script type="application/json" id="oli-gallery-data">[[! galleryDataJson !]]</script>
        [% endif %]

        [% if hasPhotos %]
        <section class="gallery gallery--photos" data-gallery-photos>
            <div class="gallery__main" data-gallery-main>
                <figure class="gallery__main-figure">
                    <img class="gallery__main-image" src="[[ photos[0].url ]]"[% if photos[0].srcset %] srcset="[[ photos[0].srcset ]]" sizes="(max-width: 900px) 100vw, 720px"[% endif %] alt="[[ photos[0].alt ]]" data-gallery-main-image>
                    <figcaption class="gallery__main-caption" data-gallery-main-caption>[[ photos[0].caption ]]</figcaption>
                </figure>
            </div>
            <ol class="gallery__thumbs" data-gallery-thumbs-list>
                [% for photo in photos %]
                    <li class="gallery__thumb-item">
                        <button type="button"
                                class="gallery__thumb-button"
                                data-gallery-thumb
                                data-url="[[ photo.url ]]"
                                data-srcset="[[ photo.srcset ]]"
                                data-alt="[[ photo.alt ]]"
                                data-caption="[[ photo.caption ]]">
                            <img class="gallery__thumb-image" src="[[ photo.thumb ]]"[% if photo.srcset %] srcset="[[ photo.srcset ]]" sizes="160px"[% endif %] alt="[[ photo.alt ]]" loading="lazy">
                        </button>
                    </li>
                [% endfor %]
            </ol>
        </section>
        [% endif %]

        [% if hasAnyGallery == false %]
            <p class="gallery__empty">Aucune photo n'a encore été ajoutée. Configurez la galerie depuis Apparence &gt; Galerie ou via Médias &gt; Dossiers.</p>
        [% endif %]

        [% if bodyHtml %]
        <div class="page__content">[[! bodyHtml !]]</div>
        [% endif %]
    </article>
[% endblock %]
