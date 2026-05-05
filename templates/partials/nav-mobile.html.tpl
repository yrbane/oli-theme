[# Navigation principale — version mobile (drawer).
   Variables attendues: primaryMenu (MenuItemEntity[]).
   Le drawer est masqué par défaut en CSS et révélé par menu-mobile.js.
#]
[% if primaryMenu %]
<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-mobile" data-nav-toggle>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__label">Menu</span>
</button>
<nav id="nav-mobile" class="nav nav--mobile" aria-label="Menu mobile" hidden data-nav-mobile>
    <ul class="nav__list nav__list--root">
        [% for item in primaryMenu %]
            <li class="nav__item[% if item.children %] nav__item--has-children[% endif %]">
                <a class="nav__link" href="[[ item.url ]]" [% if item.isCurrent %]aria-current="page"[% endif %]>[[ item.label ]]</a>
                [% if item.children %]
                    <ul class="nav__sublist">
                        [% for child in item.children %]
                            <li class="nav__item nav__item--child">
                                <a class="nav__link nav__link--child" href="[[ child.url ]]">[[ child.label ]]</a>
                            </li>
                        [% endfor %]
                    </ul>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
</nav>
[% endif %]
