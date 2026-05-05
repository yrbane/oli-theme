[# Navigation principale — version desktop.
   Variables attendues:
     - primaryMenu (MenuItemEntity[])
#]
[% if primaryMenu %]
<nav class="nav nav--desktop" aria-label="Menu principal" data-nav>
    <ul class="nav__list nav__list--root">
        [% for item in primaryMenu %]
            <li class="nav__item[% if item.isCurrent %] nav__item--current[% endif %][% if item.isAncestor %] nav__item--ancestor[% endif %][% if item.children %] nav__item--has-children[% endif %]">
                <a class="nav__link"
                   href="[[ item.url ]]"
                   [% if item.target %]target="[[ item.target ]]" rel="noopener"[% endif %]
                   [% if item.isCurrent %]aria-current="page"[% endif %]>
                    [[ item.label ]]
                </a>
                [% if item.children %]
                    <ul class="nav__sublist">
                        [% for child in item.children %]
                            <li class="nav__item nav__item--child[% if child.isCurrent %] nav__item--current[% endif %]">
                                <a class="nav__link nav__link--child"
                                   href="[[ child.url ]]"
                                   [% if child.target %]target="[[ child.target ]]" rel="noopener"[% endif %]
                                   [% if child.isCurrent %]aria-current="page"[% endif %]>
                                    [[ child.label ]]
                                </a>
                            </li>
                        [% endfor %]
                    </ul>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
</nav>
[% endif %]
