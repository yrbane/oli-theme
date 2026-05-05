[# Section <head> SEO complète. Variable: seo (SeoHeadViewModel). #]
[% if seo %]
<title>[[ seo.title ]]</title>
<meta name="description" content="[[ seo.description ]]">
<meta name="robots" content="[[ seo.robots ]]">
<link rel="canonical" href="[[ seo.canonical ]]">
[% for h in seo.hreflangs %]
<link rel="alternate" hreflang="[[ h.code ]]" href="[[ h.url ]]">
[% endfor %]
[% for k, v in seo.og %]
<meta property="[[ k ]]" content="[[ v ]]">
[% endfor %]
[% for k, v in seo.twitter %]
<meta name="[[ k ]]" content="[[ v ]]">
[% endfor %]
<script type="application/ld+json">[[! seo.jsonLd !]]</script>
[% endif %]
