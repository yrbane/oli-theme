<?php /* DEPENDENCIES: /home/seb/Dev/olikalari.com/templates/layouts/base.html.tpl */ ?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($lang, 'code') ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($lang, 'direction') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="<?= htmlspecialchars((string)($charset ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($seo)): ?>
        [% include 'partials/seo-head.html.tpl' %]
    <?php else: ?>
        <title><?= htmlspecialchars((string)($siteName ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
    <?php endif; ?>
    <?= $this->callMacro('wpHead', []) ?>
    
</head>
<body class="<?= htmlspecialchars((string)($bodyClasses ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <a class="skip-link" href="#main">Aller au contenu</a>
    
        [% include 'partials/header.html.tpl' %]
    
    
    <main id="main" class="site-main">
        
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'slug') ?? ''), ENT_QUOTES, 'UTF-8') ?>" lang="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get(\Lunar\Template\Runtime\Access::get($post, 'language'), 'code') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <header class="page__header">
            <h1 class="page__title"><?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'title') ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        <?php if (!empty(\Lunar\Template\Runtime\Access::get($post, 'featuredImageUrl'))): ?>
            <figure class="page__featured">
                <img
                    class="page__featured-image"
                    src="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'featuredImageUrl') ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'featuredImageAlt') ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    loading="lazy">
            </figure>
        <?php endif; ?>
        <div class="page__content">
            <?= \Lunar\Template\Runtime\Access::get($post, 'content') ?? '' ?>
        </div>
    </article>

    </main>
    
    [% include 'partials/footer.html.tpl' %]
    <?= $this->callMacro('wpFooter', []) ?>
    
</body>
</html>
