[# Template de page d'accueil.
   En attendant le carrousel (Plan Slides) et le SEO complet, cette page
   réutilise simplement le template `pages/page.html.tpl` lorsqu'une page
   d'accueil statique est définie. Si aucune page d'accueil statique n'est
   configurée, on délègue à l'archive des posts. Ce template sert de
   garde-fou minimal.
   Variables: post (PostEntity|null), posts (PostEntity[]|null), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
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
