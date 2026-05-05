[# Métabox SEO. Variables: meta (SeoMeta), additionalKeywords (string). #]
<div class="oli-seo-metabox">
    <p>
        <label for="oli-seo-title"><strong>Titre SEO</strong></label>
        <input type="text" id="oli-seo-title" name="seo_title" value="[[ meta.title ]]" maxlength="65" class="widefat">
        <span class="description">Recommandé : 30-65 caractères.</span>
    </p>
    <p>
        <label for="oli-seo-description"><strong>Méta description</strong></label>
        <textarea id="oli-seo-description" name="seo_description" rows="3" maxlength="158" class="widefat">[[ meta.description ]]</textarea>
        <span class="description">Recommandé : 120-158 caractères.</span>
    </p>
    <p>
        <label for="oli-seo-keyword"><strong>Mot-clé focus</strong></label>
        <input type="text" id="oli-seo-keyword" name="focus_keyword" value="[[ meta.focusKeyword ]]" class="widefat">
    </p>
    <p>
        <label for="oli-seo-additional"><strong>Mots-clés secondaires</strong></label>
        <input type="text" id="oli-seo-additional" name="additional_keywords" value="[[ additionalKeywords ]]" class="widefat" placeholder="séparés par virgule">
    </p>
    <p>
        <label for="oli-seo-og-image"><strong>Image Open Graph (ID)</strong></label>
        <input type="number" id="oli-seo-og-image" name="og_image_id" value="[[ meta.ogImageId ]]" class="small-text">
    </p>
    <p>
        <label for="oli-seo-twitter"><strong>Type de Twitter Card</strong></label>
        <select id="oli-seo-twitter" name="twitter_card_type">
            <option value="summary"[% if meta.twitterCardType == 'summary' %] selected[% endif %]>Résumé simple</option>
            <option value="summary_large_image"[% if meta.twitterCardType == 'summary_large_image' %] selected[% endif %]>Résumé avec grande image</option>
        </select>
    </p>
    <p>
        <label><input type="checkbox" name="noindex" value="1"[% if meta.noindex %] checked[% endif %]> noindex</label>
        <label style="margin-left: 1rem;"><input type="checkbox" name="nofollow" value="1"[% if meta.nofollow %] checked[% endif %]> nofollow</label>
    </p>
    <p>
        <label for="oli-seo-canonical"><strong>URL canonique (override)</strong></label>
        <input type="url" id="oli-seo-canonical" name="canonical" value="[[ meta.canonical ]]" class="widefat">
    </p>
    <p>
        <label for="oli-seo-priority"><strong>Priorité sitemap</strong></label>
        <input type="number" id="oli-seo-priority" name="priority" value="[[ meta.priority ]]" min="0" max="1" step="0.1" class="small-text">
        <label for="oli-seo-changefreq" style="margin-left: 1rem;"><strong>Changefreq</strong></label>
        <select id="oli-seo-changefreq" name="changefreq">
            <option value=""></option>
            <option value="always"[% if meta.changefreq == 'always' %] selected[% endif %]>always</option>
            <option value="hourly"[% if meta.changefreq == 'hourly' %] selected[% endif %]>hourly</option>
            <option value="daily"[% if meta.changefreq == 'daily' %] selected[% endif %]>daily</option>
            <option value="weekly"[% if meta.changefreq == 'weekly' %] selected[% endif %]>weekly</option>
            <option value="monthly"[% if meta.changefreq == 'monthly' %] selected[% endif %]>monthly</option>
            <option value="yearly"[% if meta.changefreq == 'yearly' %] selected[% endif %]>yearly</option>
            <option value="never"[% if meta.changefreq == 'never' %] selected[% endif %]>never</option>
        </select>
    </p>
</div>
