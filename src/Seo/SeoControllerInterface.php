<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;

/**
 * Contrat du controller SEO (assemble le <head> complet).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface SeoControllerInterface
{
    public function buildForPost(PostEntity $post): SeoHeadViewModel;
    public function buildForEvent(EventEntity $event): SeoHeadViewModel;
    public function buildForArchive(string $type, Language $language): SeoHeadViewModel;
    public function buildForSearch(string $query, Language $language): SeoHeadViewModel;
    public function buildFor404(Language $language): SeoHeadViewModel;
}
