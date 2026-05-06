[# Metabox « Traductions » affichée sur l'écran d'édition des posts/pages.
   Variables :
   - entries (array) : liste des traductions liées (code, label, flag, postId)
   - hasEntries (bool)
   - groupId (string) : identifiant du groupe de traduction (peut être vide)
   - nonce (string)
   - nonceField (string)
   - field (string)
#]
<div class="oli-language-metabox">
    <input type="hidden" name="[[ nonceField ]]" value="[[ nonce ]]">

    <p>
        <label for="[[ field ]]"><strong>Groupe de traduction</strong></label>
        <input type="text" id="[[ field ]]" name="[[ field ]]" value="[[ groupId ]]" class="widefat" placeholder="ex. group-about">
        <span class="description">Lie cette page à ses traductions. Donnez le même identifiant aux versions FR/EN/IT/ES d'un même contenu.</span>
    </p>

    [% if hasEntries %]
    <p><strong>Traductions liées</strong></p>
    <ul class="oli-language-metabox__list">
        [% for entry in entries %]
            <li><a href="post.php?post=[[ entry.postId ]]&action=edit">[[ entry.flag ]] [[ entry.label ]]</a></li>
        [% endfor %]
    </ul>
    [% else %]
    <p class="description">Aucune traduction liée pour ce groupe.</p>
    [% endif %]
</div>
