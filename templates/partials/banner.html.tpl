[# Bannière du site (titre + slogan cliquables).
   Variables attendues:
     - homeUrl     (string)
     - siteName    (string)
   Variable globale (macro Lunar) :
     - siteTagline (string|empty) : description WordPress (slogan).
#]
<div class="banner" data-banner>
    <a class="banner__home" href="[[ homeUrl ]]" aria-label="[[ siteName ]] — accueil">
        <span class="banner__title">[[ siteName ]]</span>
        [% if siteTagline %]
            <span class="banner__tagline">[[ siteTagline ]]</span>
        [% endif %]
    </a>
</div>
