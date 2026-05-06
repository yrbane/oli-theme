[# Page admin Outils > Redirections.
   Variables :
   - redirects (array) : lignes enrichies (id, source, target, code, hits, edit_url, delete_url)
   - editing (array|null) : redirection en cours d'édition (id/source/target/code)
   - is_editing (bool)
   - notice (string)
   - save_url (string), action_save (string), nonce_save (string), cancel_url (string)
   - page (int), total_pages (int), total (int), has_pages (bool), prev_page, next_page
   - list_empty (bool)
#]
<div class="wrap oli-redirects">
    <h1>Redirections</h1>

    [% if notice %]
        [% if notice == "created" %]
        <div class="notice notice-success is-dismissible"><p>Redirection créée.</p></div>
        [% endif %]
        [% if notice == "updated" %]
        <div class="notice notice-success is-dismissible"><p>Redirection mise à jour.</p></div>
        [% endif %]
        [% if notice == "deleted" %]
        <div class="notice notice-success is-dismissible"><p>Redirection supprimée.</p></div>
        [% endif %]
        [% if notice == "invalid_source" %]
        <div class="notice notice-error is-dismissible"><p>Source invalide : doit commencer par « / ».</p></div>
        [% endif %]
        [% if notice == "missing_target" %]
        <div class="notice notice-error is-dismissible"><p>La cible est obligatoire pour les codes 301 et 302.</p></div>
        [% endif %]
        [% if notice == "invalid_code" %]
        <div class="notice notice-error is-dismissible"><p>Code HTTP non autorisé (301, 302 ou 410).</p></div>
        [% endif %]
        [% if notice == "invalid" %]
        <div class="notice notice-error is-dismissible"><p>Action invalide.</p></div>
        [% endif %]
    [% endif %]

    <h2 class="title">[% if is_editing %]Modifier la redirection[% else %]Ajouter une redirection[% endif %]</h2>
    <form action="[[ save_url ]]" method="post" class="oli-redirects__form card">
        <input type="hidden" name="action" value="[[ action_save ]]">
        <input type="hidden" name="_wpnonce" value="[[ nonce_save ]]">
        [% if is_editing %]
        <input type="hidden" name="id" value="[[ editing.id ]]">
        [% else %]
        <input type="hidden" name="id" value="">
        [% endif %]

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="oli-redirect-source">Source</label></th>
                    <td>
                        <input type="text" id="oli-redirect-source" name="source" value="[% if is_editing %][[ editing.source ]][% endif %]" placeholder="/ancienne-page" required>
                        <p class="description">Chemin relatif à la racine, doit commencer par « / ».</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="oli-redirect-target">Cible</label></th>
                    <td>
                        <input type="text" id="oli-redirect-target" name="target" value="[% if is_editing %][[ editing.target ]][% endif %]" placeholder="https://exemple.fr/nouvelle-page">
                        <p class="description">URL absolue ou chemin relatif. Optionnel pour le code 410.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="oli-redirect-code">Code HTTP</label></th>
                    <td>
                        <select id="oli-redirect-code" name="code">
                            <option value="301"[% if is_editing %][% if editing.code == 301 %] selected[% endif %][% else %] selected[% endif %]>301 — Redirection permanente</option>
                            <option value="302"[% if is_editing %][% if editing.code == 302 %] selected[% endif %][% endif %]>302 — Redirection temporaire</option>
                            <option value="410"[% if is_editing %][% if editing.code == 410 %] selected[% endif %][% endif %]>410 — Ressource supprimée</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">[% if is_editing %]Enregistrer les modifications[% else %]Ajouter[% endif %]</button>
            [% if is_editing %]
            <a href="[[ cancel_url ]]" class="button button-secondary">Annuler</a>
            [% endif %]
        </p>
    </form>

    <h2 class="title">Redirections enregistrées <span class="count">([[ total ]])</span></h2>
    [% if list_empty %]
    <p>Aucune redirection enregistrée.</p>
    [% else %]
    <table class="widefat striped oli-redirects__table">
        <thead>
            <tr>
                <th>Source</th>
                <th>Cible</th>
                <th>Code</th>
                <th class="num">Hits</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            [% for r in redirects %]
            <tr>
                <td><code>[[ r.source ]]</code></td>
                <td><code>[[ r.target ]]</code></td>
                <td>[[ r.code ]]</td>
                <td class="num">[[ r.hits ]]</td>
                <td class="actions">
                    <a href="[[ r.edit_url ]]" class="button button-small">Modifier</a>
                    <a href="[[ r.delete_url ]]" class="button button-small button-link-delete" onclick="return confirm('Supprimer cette redirection ?');">Supprimer</a>
                </td>
            </tr>
            [% endfor %]
        </tbody>
    </table>

    [% if has_pages %]
    <nav class="oli-redirects__pagination tablenav">
        <span class="displaying-num">Page [[ page ]] / [[ total_pages ]]</span>
        [% if prev_page %]<a class="button" href="[[ prev_page ]]">‹ Précédent</a>[% endif %]
        [% if next_page %]<a class="button" href="[[ next_page ]]">Suivant ›</a>[% endif %]
    </nav>
    [% endif %]
    [% endif %]
</div>
