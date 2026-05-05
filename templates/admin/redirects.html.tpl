<div class="wrap">
    <h1>Redirections</h1>
    [% if redirects %]
    <table class="widefat">
        <thead><tr><th>Source</th><th>Cible</th><th>Code</th><th>Hits</th></tr></thead>
        <tbody>
            [% for r in redirects %]
            <tr>
                <td>[[ r.source ]]</td>
                <td>[[ r.target ]]</td>
                <td>[[ r.code ]]</td>
                <td>[[ r.hits ]]</td>
            </tr>
            [% endfor %]
        </tbody>
    </table>
    [% else %]
    <p>Aucune redirection enregistrée. (UI d'ajout MVP — extension ultérieure prévue.)</p>
    [% endif %]
</div>
