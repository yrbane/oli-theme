[# Template d'archive des posts.
   Variables: posts (PostEntity[]), archiveTitle (string), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <section class="archive archive-post">
        <header class="archive__header">
            <h1 class="archive__title">
                [% if archiveTitle %][[ archiveTitle ]][% else %]Actualités[% endif %]
            </h1>
        </header>
        [% if posts %]
            <ul class="archive__list archive__list--cards">
                [% for post in posts %]
                    <li class="archive__item">
                        <article class="post-card">
                            <a class="post-card__link" href="[[ post.permalink ]]" aria-label="[[ post.title ]]">
                                [% if post.featuredImageUrl %]
                                    <span class="post-card__thumb">
                                        <img src="[[ post.featuredImageUrl ]]"
                                             alt="[% if post.featuredImageAlt %][[ post.featuredImageAlt ]][% else %][[ post.title ]][% endif %]"
                                             loading="lazy"
                                             decoding="async" />
                                    </span>
                                [% else %]
                                    <span class="post-card__thumb post-card__thumb--empty" aria-hidden="true"></span>
                                [% endif %]
                                <span class="post-card__body">
                                    <h3 class="post-card__title">[[ post.title ]]</h3>
                                    <p class="post-card__meta">
                                        <time datetime="[[ post.publishedAt.format('c') ]]">
                                            [[ post.publishedAt.format('d/m/Y') ]]
                                        </time>
                                    </p>
                                    [% if post.excerpt %]
                                        <div class="post-card__excerpt">[[! post.excerpt !]]</div>
                                    [% endif %]
                                </span>
                            </a>
                        </article>
                    </li>
                [% endfor %]
            </ul>
        [% else %]
            <p class="archive__empty">Aucune publication pour le moment.</p>
        [% endif %]
    </section>
[% endblock %]
