<?php /* DEPENDENCIES: /home/seb/Dev/olikalari.com/templates/layouts/base.html.tpl */ ?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($lang, 'code') ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($lang, 'direction') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="<?= htmlspecialchars((string)($charset ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($seo)): ?>
        <?= $this->renderInclude('partials/seo-head.html.tpl', array_merge(get_defined_vars(), [])) ?>
    <?php else: ?>
        <title><?= htmlspecialchars((string)($siteName ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
    <?php endif; ?>
    <?= $this->callMacro('wpHead', []) ?>
    
</head>
<body class="<?= htmlspecialchars((string)($bodyClasses ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= $this->callMacro('extraBodyClass', []) ?>">
    <a class="skip-link" href="#main">Aller au contenu</a>
    
        <?= $this->renderInclude('partials/header.html.tpl', array_merge(get_defined_vars(), [])) ?>
    
    
    <main id="main" class="site-main">
        
    <?= $this->renderInclude('partials/breadcrumbs.html.tpl', array_merge(get_defined_vars(), [])) ?>
    <article class="page page--<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'slug') ?? ''), ENT_QUOTES, 'UTF-8') ?>" lang="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get(\Lunar\Template\Runtime\Access::get($post, 'language'), 'code') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($coverHtml)): ?>
            <div class="page__cover"><?= $coverHtml ?? '' ?></div>
        <?php elseif (!empty(\Lunar\Template\Runtime\Access::get($post, 'featuredImageUrl'))): ?>
            <figure class="page__featured">
                <img
                    class="page__featured-image"
                    src="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'featuredImageUrl') ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'featuredImageAlt') ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    loading="lazy">
            </figure>
        <?php endif; ?>
        <header class="page__header">
            <h1 class="page__title"><?= htmlspecialchars((string)(\Lunar\Template\Runtime\Access::get($post, 'title') ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        <div class="page__content">
            <?= $bodyHtml ?? '' ?>
        </div>
    </article>

    </main>
    
    <?= $this->renderInclude('partials/footer.html.tpl', array_merge(get_defined_vars(), [])) ?>
    <?= $this->callMacro('wpFooter', []) ?>
    
</body>
</html>
