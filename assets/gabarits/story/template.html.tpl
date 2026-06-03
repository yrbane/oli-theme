<?php /** @var array<string, string> $zones */ ?>
<article class="story">
    <?php if (!empty($zones['intro'])): ?>
        <header class="story__intro"><?= $zones['intro'] ?></header>
    <?php endif; ?>
    <?php if (!empty($zones['gallery'])): ?>
        <section class="story__gallery"><?= $zones['gallery'] ?></section>
    <?php endif; ?>
    <?php if (!empty($zones['quote'])): ?>
        <blockquote class="story__quote"><?= $zones['quote'] ?></blockquote>
    <?php endif; ?>
    <?php if (!empty($zones['conclusion'])): ?>
        <div class="story__conclusion"><?= $zones['conclusion'] ?></div>
    <?php endif; ?>
</article>
