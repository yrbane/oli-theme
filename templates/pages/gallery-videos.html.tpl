[# Page Galerie Vidéos : layout vignettes-gauche / iframe-droite.
   Variables : post (PostEntity), videos (array), hasVideos (bool),
              channelUrl (string), + base. #]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--gallery page--gallery-videos page--[[ post.slug ]]" lang="[[ post.language.code ]]">
        <header class="page__header">
            <h1 class="page__title">[[ post.title ]]</h1>
        </header>

        [% if hasVideos %]
        <section class="gallery gallery--videos" data-gallery-videos>
            <div class="gallery__main" data-gallery-main>
                <div class="gallery__main-frame">
                    <iframe class="gallery__main-iframe"
                            data-gallery-main-iframe
                            src="[[ videos[0].embed_url ]]"
                            title="[[ videos[0].caption ]]"
                            loading="lazy"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
                <p class="gallery__main-caption" data-gallery-main-caption>[[ videos[0].caption ]]</p>
            </div>
            <ol class="gallery__thumbs">
                [% for video in videos %]
                    <li class="gallery__thumb-item">
                        <button type="button"
                                class="gallery__thumb-button"
                                data-gallery-thumb
                                data-embed="[[ video.embed_url ]]"
                                data-caption="[[ video.caption ]]">
                            <span class="gallery__thumb-play" aria-hidden="true">▶</span>
                            <span class="gallery__thumb-title">[[ video.caption ]]</span>
                        </button>
                    </li>
                [% endfor %]
            </ol>
        </section>
        [% else %]
        <p class="gallery__empty">Aucune vidéo n'a encore été ajoutée. Configurez la galerie depuis Apparence &gt; Galerie.</p>
        [% endif %]

        [% if bodyHtml %]
        <div class="page__content">[[! bodyHtml !]]</div>
        [% endif %]
    </article>
[% endblock %]
