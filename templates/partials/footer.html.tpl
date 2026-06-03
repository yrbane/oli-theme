[# Pied de page.
   Variables attendues:
     - footerMenu  (MenuItemEntity[])  optionnel
   Variables globales: siteName, currentYear, footerLogoUrl, footerText.
#]
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        [% if footerLogoUrl %]
            <p class="site-footer__brand">
                <a href="[[ homeUrl ]]" aria-label="[[ siteName ]]">
                    <img class="site-footer__logo" src="[[ footerLogoUrl ]]" alt="[[ siteName ]]" loading="lazy" />
                </a>
            </p>
        [% endif %]
        [% if footerMenu %]
            <nav class="site-footer__nav" aria-label="Menu pied de page">
                <ul class="site-footer__list">
                    [% for item in footerMenu %]
                        <li class="site-footer__item">
                            <a href="[[ item.url ]]">[[ item.label ]]</a>
                        </li>
                    [% endfor %]
                </ul>
            </nav>
        [% endif %]
        ##socialIcons()##
        [% if footerText %]
            <div class="site-footer__text">[[ footerText|raw ]]</div>
        [% endif %]
        <p class="site-footer__copy">© [[ currentYear ]] [[ siteName ]]. Tous droits réservés.</p>
    </div>
</footer>
