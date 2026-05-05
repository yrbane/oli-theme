[# Template d'article singulier.
   Variables attendues: post (PostEntity), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="post post--single post--id-[[ post.id ]]" lang="[[ post.language.code ]]">
        <header class="post__header">
            <h1 class="post__title">[[ post.title ]]</h1>
            <p class="post__meta">
                <time datetime="[[ post.publishedAt.format('c') ]]">
                    [[ post.publishedAt.format('d/m/Y') ]]
                </time>
                [% if post.author %]
                    <span class="post__author">— [[ post.author ]]</span>
                [% endif %]
            </p>
        </header>
        [% if post.featuredImageUrl %]
            <figure class="post__featured">
                <img class="post__featured-image"
                     src="[[ post.featuredImageUrl ]]"
                     alt="[[ post.featuredImageAlt ]]"
                     loading="lazy">
            </figure>
        [% endif %]
        <div class="post__content">
            [[! post.content !]]
        </div>
    </article>
[% endblock %]
