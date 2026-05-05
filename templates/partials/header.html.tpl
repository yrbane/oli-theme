[# Header du thème.
   Variables attendues:
     - lang             (Language)
     - languageSwitcher (LanguageSwitcherViewModel)
   Variables globales: homeUrl, siteName.
#]
<header class="site-header" role="banner">
    [% include 'partials/banner.html.tpl' %]
    <nav class="site-nav" aria-label="Menu principal">
        [# Le menu principal sera injecté par le module Navigation (plan ultérieur).
           Pour l'instant, un lien d'accueil minimal. #]
        <ul class="site-nav__list">
            <li class="site-nav__item">
                <a class="site-nav__link" href="[[ homeUrl ]]">Accueil</a>
            </li>
        </ul>
    </nav>
    [% if languageSwitcher.items %]
        <ul class="language-switcher" aria-label="Changer de langue">
            [% for item in languageSwitcher.items %]
                <li class="language-switcher__item[% if item.isCurrent %] language-switcher__item--current[% endif %]">
                    <a href="[[ item.url ]]" hreflang="[[ item.code ]]" lang="[[ item.code ]]">
                        [[ item.label ]]
                    </a>
                </li>
            [% endfor %]
        </ul>
    [% endif %]
</header>
