[# Template 404 — variables: lang, languageSwitcher, homeUrl. #]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    <section class="error-404">
        <h1 class="error-404__title">Page introuvable</h1>
        <p class="error-404__text">La page que vous recherchez n'existe pas (ou plus).</p>
        <p>
            <a class="btn btn--primary" href="[[ homeUrl ]]">Retour à l'accueil</a>
        </p>
    </section>
[% endblock %]
