[# Template d'archive des événements.
   Variables: upcomingEvents (EventEntity[]), pastEvents (EventEntity[]), archiveTitle (string),
              lang, languageSwitcher, primaryMenu, footerMenu, bodyClasses.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <section class="archive archive--event">
        <header class="archive__header">
            <h1 class="archive__title">
                [% if archiveTitle %][[ archiveTitle ]][% else %]Événements[% endif %]
            </h1>
        </header>

        <div class="archive__section archive__section--upcoming">
            <h2 class="archive__section-title">Événements à venir</h2>
            [% if upcomingEvents %]
                <div class="archive__grid">
                    [% for event in upcomingEvents %]
                        [% include 'partials/event-card.html.tpl' %]
                    [% endfor %]
                </div>
            [% else %]
                <p class="archive__empty">Aucun événement à venir.</p>
            [% endif %]
        </div>

        <div class="archive__section archive__section--past">
            <h2 class="archive__section-title">Événements passés</h2>
            [% if pastEvents %]
                <div class="archive__grid">
                    [% for event in pastEvents %]
                        [% include 'partials/event-card.html.tpl' %]
                    [% endfor %]
                </div>
            [% else %]
                <p class="archive__empty">Aucun événement passé.</p>
            [% endif %]
        </div>
    </section>
[% endblock %]
