[# Template de page singulière.
   Variables attendues: post (PostEntity), lang (Language), languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--[[ post.slug ]]" lang="[[ post.language.code ]]">
        <header class="page__header">
            <h1 class="page__title">[[ post.title ]]</h1>
        </header>
        [% if post.featuredImageUrl %]
            <figure class="page__featured">
                <img
                    class="page__featured-image"
                    src="[[ post.featuredImageUrl ]]"
                    alt="[[ post.featuredImageAlt ]]"
                    loading="lazy">
            </figure>
        [% endif %]
        <div class="page__content">
            [[! post.content !]]
        </div>
    </article>
[% endblock %]
