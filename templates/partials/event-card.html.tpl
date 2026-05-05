[# Carte événement réutilisable.
   Variables attendues: event (EventEntity).
#]
<article class="event event--card[% if event.isPast %] event--past[% endif %][% if event.isOngoing %] event--ongoing[% endif %]"
         itemscope itemtype="https://schema.org/Event">
    <h3 class="event__title">
        <a href="[[ event.permalink ]]" itemprop="url">
            <span itemprop="name">[[ event.title ]]</span>
        </a>
    </h3>
    <p class="event__date">
        <time datetime="[[ event.startDate.format('c') ]]" itemprop="startDate">
            [[ event.startDate.format('d/m/Y') ]]
        </time>
    </p>
    [% if event.location %]
        <p class="event__location">[[ event.location ]]</p>
    [% endif %]
    [% if event.excerpt %]
        <div class="event__excerpt">[[! event.excerpt !]]</div>
    [% endif %]
</article>
