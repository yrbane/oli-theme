[# Fil d'Ariane.
   Sera alimenté par BreadcrumbsController dans le module SEO (plan ultérieur).
   Variable attendue: crumbs (array<{label, url, isCurrent}>) — peut être absente.
#]
[% if crumbs %]
<nav class="breadcrumbs" aria-label="Fil d'Ariane">
    <ol class="breadcrumbs__list">
        [% for crumb in crumbs %]
            <li class="breadcrumbs__item">
                [% if crumb.isCurrent %]
                    <span aria-current="page">[[ crumb.label ]]</span>
                [% else %]
                    <a href="[[ crumb.url ]]">[[ crumb.label ]]</a>
                [% endif %]
            </li>
        [% endfor %]
    </ol>
</nav>
[% endif %]
