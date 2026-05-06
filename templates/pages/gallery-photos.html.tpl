[# Page Galerie Photos : layout vignettes-gauche / grande-droite.
   Variables : post (PostEntity), photos (array), hasPhotos (bool), + base. #]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--gallery page--gallery-photos page--[[ post.slug ]]" lang="[[ post.language.code ]]">
        <header class="page__header">
            <h1 class="page__title">[[ post.title ]]</h1>
        </header>

        [% if hasPhotos %]
        <section class="gallery gallery--photos" data-gallery-photos>
            <div class="gallery__main" data-gallery-main>
                <figure class="gallery__main-figure">
                    <img class="gallery__main-image" src="[[ photos[0].url ]]" alt="[[ photos[0].alt ]]" data-gallery-main-image>
                    <figcaption class="gallery__main-caption" data-gallery-main-caption>[[ photos[0].caption ]]</figcaption>
                </figure>
            </div>
            <ol class="gallery__thumbs">
                [% for photo in photos %]
                    <li class="gallery__thumb-item">
                        <button type="button"
                                class="gallery__thumb-button"
                                data-gallery-thumb
                                data-url="[[ photo.url ]]"
                                data-alt="[[ photo.alt ]]"
                                data-caption="[[ photo.caption ]]">
                            <img class="gallery__thumb-image" src="[[ photo.thumb ]]" alt="[[ photo.alt ]]" loading="lazy">
                        </button>
                    </li>
                [% endfor %]
            </ol>
        </section>
        [% else %]
        <p class="gallery__empty">Aucune photo n'a encore été ajoutée. Configurez la galerie depuis Apparence &gt; Galerie.</p>
        [% endif %]

        [% if bodyHtml %]
        <div class="page__content">[[! bodyHtml !]]</div>
        [% endif %]
    </article>
[% endblock %]
