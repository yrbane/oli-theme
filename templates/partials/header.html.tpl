[# Header du thème.
   Variables attendues:
     - lang             (Language)
     - languageSwitcher (LanguageSwitcherViewModel)
     - primaryMenu      (MenuItemEntity[])
   Variables globales: homeUrl, siteName.
#]
<header class="site-header" role="banner">
    [% include 'partials/banner.html.tpl' %]
    [% include 'partials/nav-desktop.html.tpl' %]
    [% include 'partials/nav-mobile.html.tpl' %]
    [% if languageSwitcher.items %]
        <ul class="language-switcher" aria-label="Changer de langue">
            [% for item in languageSwitcher.items %]
                <li class="language-switcher__item[% if item.isCurrent %] language-switcher__item--current[% endif %]">
                    <a href="[[ item.url ]]" hreflang="[[ item.code ]]" lang="[[ item.code ]]" title="[[ item.label ]]" aria-label="[[ item.label ]]">
                        [% if item.flagUrl %]
                            <img class="language-switcher__flag language-switcher__flag--svg" src="[[ item.flagUrl ]]" alt="" aria-hidden="true" width="20" height="15" loading="lazy" decoding="async">
                        [% else %]
                            <span class="language-switcher__flag" aria-hidden="true">[[ item.flag ]]</span>
                        [% endif %]
                        <span class="screen-reader-text">[[ item.label ]]</span>
                    </a>
                </li>
            [% endfor %]
        </ul>
    [% endif %]
</header>
