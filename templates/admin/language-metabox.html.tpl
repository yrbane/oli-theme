[# Metabox « Traductions » affichée sur l'écran d'édition des posts/pages.
   Variables :
   - languages (Language[]) : langues activées
   - translations (array<string, int>) : code langue -> postId existant
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

    [% if translations %]
    <p><strong>Traductions liées</strong></p>
    <ul class="oli-language-metabox__list">
        [% for lang in languages %]
            [% if translations[lang.code] %]
                <li><a href="post.php?post=[[ translations[lang.code] ]]&action=edit">[[ lang.flag ]] [[ lang.label ]]</a></li>
            [% endif %]
        [% endfor %]
    </ul>
    [% else %]
    <p class="description">Aucune traduction liée pour ce groupe.</p>
    [% endif %]
</div>
