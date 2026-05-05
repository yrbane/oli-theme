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
            <ul class="archive__list">
                [% for post in posts %]
                    <li class="archive__item">
                        <article class="post post--card">
                            <h2 class="post__title">
                                <a href="[[ post.permalink ]]">[[ post.title ]]</a>
                            </h2>
                            <p class="post__meta">
                                <time datetime="[[ post.publishedAt.format('c') ]]">
                                    [[ post.publishedAt.format('d/m/Y') ]]
                                </time>
                            </p>
                            [% if post.excerpt %]
                                <div class="post__excerpt">[[! post.excerpt !]]</div>
                            [% endif %]
                        </article>
                    </li>
                [% endfor %]
            </ul>
        [% else %]
            <p class="archive__empty">Aucune publication pour le moment.</p>
        [% endif %]
    </section>
[% endblock %]
