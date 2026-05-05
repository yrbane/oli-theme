[# Carousel d'accueil (slides Oli).
   Variables attendues:
     - carousel (HomeCarouselViewModel) avec slides[], autoplay, intervalMs, loop.
   Sans JS: la première slide est visible (CSS gère via scroll-snap).
#]
[% if carousel.slides %]
<section class="carousel" data-carousel
         data-autoplay="[% if carousel.autoplay %]true[% else %]false[% endif %]"
         data-interval="[[ carousel.intervalMs ]]"
         data-loop="[% if carousel.loop %]true[% else %]false[% endif %]"
         aria-roledescription="carousel"
         aria-label="Diaporama d'accueil">
    <ul class="carousel__list" role="list">
        [% for slide in carousel.slides %]
            <li class="carousel__slide" role="group" aria-roledescription="slide" aria-label="[[ slide.title ]]">
                <figure class="carousel__figure">
                    <img class="carousel__image" src="[[ slide.imageUrl ]]" alt="[[ slide.imageAlt ]]" loading="lazy">
                    [% if slide.caption %]
                        <figcaption class="carousel__caption">[[! slide.caption !]]</figcaption>
                    [% endif %]
                </figure>
                [% if slide.linkUrl %]
                    <a class="carousel__cta btn btn--primary" href="[[ slide.linkUrl ]]">
                        [% if slide.linkLabel %][[ slide.linkLabel ]][% else %]En savoir plus[% endif %]
                    </a>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
    <div class="carousel__controls" data-carousel-controls hidden>
        <button class="carousel__btn carousel__btn--prev" type="button" data-carousel-prev aria-label="Précédent">‹</button>
        <button class="carousel__btn carousel__btn--next" type="button" data-carousel-next aria-label="Suivant">›</button>
    </div>
</section>
[% endif %]
