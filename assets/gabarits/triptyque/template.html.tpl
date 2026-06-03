<?php /** @var array<string, string> $zones */ ?>
<article class="triptyque">
    <?php if (!empty($zones['intro'])): ?>
        <header class="triptyque__intro"><?= $zones['intro'] ?></header>
    <?php endif; ?>
    <?php if (!empty($zones['hero'])): ?>
        <figure class="triptyque__hero"><?= $zones['hero'] ?></figure>
    <?php endif; ?>
    <?php if (!empty($zones['development'])): ?>
        <div class="triptyque__development"><?= $zones['development'] ?></div>
    <?php endif; ?>
</article>
