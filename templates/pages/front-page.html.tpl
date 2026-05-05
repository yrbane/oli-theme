[# Template de page d'accueil.
   Si une page d'accueil statique est configurée, on rend son contenu — précédé
   du carousel (slides actifs pour la langue courante). Sinon, fallback minimal.
   Variables: post (PostEntity|null), posts (PostEntity[]|null), lang, languageSwitcher,
              carousel (HomeCarouselViewModel|null) — présent uniquement quand front-page.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/carousel.html.tpl' %]
    [% if post %]
        <article class="page page--front" lang="[[ post.language.code ]]">
            <h1 class="page__title">[[ post.title ]]</h1>
            <div class="page__content">[[! post.content !]]</div>
        </article>
    [% else %]
        <section class="front front--default">
            <h1 class="front__title">[[ siteName ]]</h1>
            <p class="front__lead">Bienvenue.</p>
        </section>
    [% endif %]
[% endblock %]
