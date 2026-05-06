[# Layout racine du thème oli-theme.
   Variables attendues:
     - lang             (Language)              langue courante
     - bodyClasses      (string)                classes <body>
     - languageSwitcher (LanguageSwitcherViewModel)
   Variables globales (injectées par ViewRenderer):
     - wpHead, wpFooter, siteName, siteUrl, homeUrl, themeUri, currentYear, charset
   Blocs surchargeables: head_extra, banner, before_main, main, after_main, footer_extra
#]
<!DOCTYPE html>
<html lang="[[ lang.code ]]" dir="[[ lang.direction ]]">
<head>
    <meta charset="[[ charset ]]">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    [% if seo %]
        [% include 'partials/seo-head.html.tpl' %]
    [% else %]
        <title>[[ siteName ]]</title>
    [% endif %]
    ##wpHead()##
    [% block head_extra %][% endblock %]
</head>
<body class="[[ bodyClasses ]] ##extraBodyClass()##">
    <a class="skip-link" href="#main">Aller au contenu</a>
    [% block banner %]
        [% include 'partials/header.html.tpl' %]
    [% endblock %]
    [% block before_main %][% endblock %]
    <main id="main" class="site-main">
        [% block main %][% endblock %]
    </main>
    [% block after_main %][% endblock %]
    [% include 'partials/footer.html.tpl' %]
    ##wpFooter()##
    [% block footer_extra %][% endblock %]
</body>
</html>
