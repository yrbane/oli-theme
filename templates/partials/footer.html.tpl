[# Pied de page.
   Variables attendues:
     - footerMenu  (MenuItemEntity[])  optionnel
   Variables globales: siteName, currentYear.
#]
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
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
        <p class="site-footer__copy">© [[ currentYear ]] [[ siteName ]]. Tous droits réservés.</p>
    </div>
</footer>
