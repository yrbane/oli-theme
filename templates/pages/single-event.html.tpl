[# Template de fiche événement singulier.
   Variables attendues: event (EventEntity), lang, languageSwitcher, primaryMenu, footerMenu, bodyClasses.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="event event--single[% if event.isPast %] event--past[% endif %][% if event.isOngoing %] event--ongoing[% endif %]"
             itemscope itemtype="https://schema.org/Event">
        <header class="event__header">
            <h1 class="event__title" itemprop="name">[[ event.title ]]</h1>
            <p class="event__date">
                <time datetime="[[ event.startDate.format('c') ]]" itemprop="startDate">
                    [[ event.startDate.format('d/m/Y H\hi') ]]
                </time>
                [% if event.endDate %]
                    <span class="event__date-sep"> – </span>
                    <time datetime="[[ event.endDate.format('c') ]]" itemprop="endDate">
                        [[ event.endDate.format('d/m/Y H\hi') ]]
                    </time>
                [% endif %]
            </p>
            [% if event.location %]
                <p class="event__location" itemprop="location">
                    [[ event.location ]]
                    [% if event.address %]
                        <span class="event__address">[[ event.address ]]</span>
                    [% endif %]
                </p>
            [% endif %]
            [% if event.price %]
                <p class="event__price">[[ event.price ]]</p>
            [% endif %]
        </header>

        [% if event.flyerUrl %]
            <p class="event__flyer">
                <a class="event__flyer-link" href="[[ event.flyerUrl ]]" target="_blank" rel="noopener">Télécharger le flyer</a>
            </p>
        [% endif %]

        <div class="event__content" itemprop="description">
            [[! event.description !]]
        </div>

        [% if event.registrationUrl %]
            <p class="event__cta-wrapper">
                <a class="btn btn--primary event__cta"
                   href="[[ event.registrationUrl ]]"
                   itemprop="url">S'inscrire</a>
            </p>
        [% endif %]
    </article>
[% endblock %]
