[# Template de page de résultats de recherche.
   Variables: query (string), posts (PostEntity[]), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    <section class="search-results">
        <header class="search-results__header">
            <h1 class="search-results__title">
                Résultats pour
                <em>[[ query ]]</em>
            </h1>
        </header>
        <form class="search-form" role="search" method="get" action="[[ homeUrl ]]">
            <label class="search-form__label" for="search-input">Rechercher</label>
            <input id="search-input" class="search-form__input" type="search" name="s" value="[[ query ]]">
            <button class="search-form__submit" type="submit">Rechercher</button>
        </form>
        [% if posts %]
            <ul class="search-results__list">
                [% for post in posts %]
                    <li class="search-results__item">
                        <a href="[[ post.permalink ]]">[[ post.title ]]</a>
                    </li>
                [% endfor %]
            </ul>
        [% else %]
            <p class="search-results__empty">Aucun résultat.</p>
        [% endif %]
    </section>
[% endblock %]
