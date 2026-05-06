[# Page admin Outils > SEO Dashboard.
   Variables :
   - title (string)
   - items (array) : id, type, status, title, score, score_class, focus_keyword,
                     title_length, description_length, edit_url
   - total (int), page, total_pages, has_pages, prev_page, next_page
   - filter_type (string), filter_min, filter_max (int|null)
   - types (array<string>)
   - reset_url (string)
   - export_url (string)
   - list_empty (bool)
#]
<div class="wrap oli-seo-dashboard">
    <h1>[[ title ]]</h1>
    <p class="description">Tableau de bord des scores SEO de tous les contenus (articles, pages, événements). Cliquez sur un titre pour ouvrir l'éditeur.</p>

    <form method="get" class="oli-seo-dashboard__filters card">
        <input type="hidden" name="page" value="oli-seo-dashboard">
        <div class="filters-row">
            <label>
                <span>Type</span>
                <select name="type">
                    <option value="">Tous</option>
                    [% for t in types %]
                        <option value="[[ t ]]"[% if filter_type == t %] selected[% endif %]>[[ t ]]</option>
                    [% endfor %]
                </select>
            </label>
            <label>
                <span>Score min.</span>
                <input type="number" name="min_score" min="0" max="100" value="[% if filter_min %][[ filter_min ]][% endif %]" placeholder="0">
            </label>
            <label>
                <span>Score max.</span>
                <input type="number" name="max_score" min="0" max="100" value="[% if filter_max %][[ filter_max ]][% endif %]" placeholder="100">
            </label>
            <div class="filters-actions">
                <button type="submit" class="button button-primary">Filtrer</button>
                <a href="[[ reset_url ]]" class="button button-secondary">Réinitialiser</a>
                <a href="[[ export_url ]]" class="button">Exporter CSV</a>
            </div>
        </div>
    </form>

    <h2 class="title">Contenus <span class="count">([[ total ]])</span></h2>

    [% if list_empty %]
    <p>Aucun contenu ne correspond aux filtres actuels.</p>
    [% else %]
    <table class="widefat striped oli-seo-dashboard__table">
        <thead>
            <tr>
                <th class="col-score">Score</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Mot-clé</th>
                <th class="num">Titre (car.)</th>
                <th class="num">Méta (car.)</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            [% for item in items %]
            <tr>
                <td class="col-score">
                    <span class="score-pill score-[[ item.score_class ]]">[[ item.score ]]</span>
                </td>
                <td>
                    <a href="[[ item.edit_url ]]"><strong>[[ item.title ]]</strong></a>
                </td>
                <td><code>[[ item.type ]]</code></td>
                <td>[[ item.status ]]</td>
                <td>[% if item.focus_keyword %]<code>[[ item.focus_keyword ]]</code>[% else %]<span class="muted">—</span>[% endif %]</td>
                <td class="num">[[ item.title_length ]]</td>
                <td class="num">[[ item.description_length ]]</td>
                <td class="actions">
                    <a href="[[ item.edit_url ]]" class="button button-small">Modifier</a>
                </td>
            </tr>
            [% endfor %]
        </tbody>
    </table>

    [% if has_pages %]
    <nav class="oli-seo-dashboard__pagination tablenav">
        <span class="displaying-num">Page [[ page ]] / [[ total_pages ]]</span>
        [% if prev_page %]<a class="button" href="[[ prev_page ]]">‹ Précédent</a>[% endif %]
        [% if next_page %]<a class="button" href="[[ next_page ]]">Suivant ›</a>[% endif %]
    </nav>
    [% endif %]
    [% endif %]
</div>
