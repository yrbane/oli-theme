[# Page Settings — Apparence > Identité du site.
   Variables: title (string), tabs (array), form (string HTML capturé).
#]
<div class="wrap oli-settings">
    <h1>[[ title ]]</h1>
    <nav class="nav-tab-wrapper" aria-label="Sections">
        [% for tab in tabs %]
            <a class="nav-tab[% if tab.isActive %] nav-tab-active[% endif %]" href="[[ tab.url ]]">[[ tab.label ]]</a>
        [% endfor %]
    </nav>
    <form action="options.php" method="post" class="oli-settings__form">
        [[! form !]]
    </form>
</div>
