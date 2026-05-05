[# Formulaire de contact sécurisé.
   Variables attendues:
     - nonce      (string)
     - timestamp  (int)         pour le time-trap
     - redirect   (string)      URL de retour
     - actionUrl  (string)      cible POST (admin-post.php)
     - errors     (array<string,string>)
     - success    (bool)
     - lang       (Language)
#]
<div class="contact-form" data-contact-form>
    [% if success %]
        <p class="contact-form__success" role="status">Votre message a bien été envoyé. Merci !</p>
    [% endif %]
    [% if errors.rate_limit %]
        <p class="contact-form__error" role="alert">Trop de tentatives. Veuillez patienter avant de réessayer.</p>
    [% endif %]
    <form class="contact-form__form" action="[[ actionUrl ]]" method="post" novalidate>
        <input type="hidden" name="action" value="oli_contact">
        <input type="hidden" name="_oli_nonce" value="[[ nonce ]]">
        <input type="hidden" name="_oli_timestamp" value="[[ timestamp ]]">
        <input type="hidden" name="_oli_redirect" value="[[ redirect ]]">

        [# Honeypot caché : doit rester vide. #]
        <div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label for="oli-contact-honeypot">Ne pas remplir</label>
            <input type="text" id="oli-contact-honeypot" name="honeypot" tabindex="-1" autocomplete="off">
        </div>

        <p class="contact-form__field[% if errors.name %] contact-form__field--error[% endif %]">
            <label class="contact-form__label" for="oli-contact-name">Nom <span aria-hidden="true">*</span></label>
            <input class="contact-form__input" type="text" id="oli-contact-name" name="name" required minlength="2" maxlength="100" autocomplete="name">
            [% if errors.name %]<span class="contact-form__error">Le nom est requis (2 à 100 caractères).</span>[% endif %]
        </p>

        <p class="contact-form__field[% if errors.email %] contact-form__field--error[% endif %]">
            <label class="contact-form__label" for="oli-contact-email">Email <span aria-hidden="true">*</span></label>
            <input class="contact-form__input" type="email" id="oli-contact-email" name="email" required autocomplete="email">
            [% if errors.email %]<span class="contact-form__error">Email invalide.</span>[% endif %]
        </p>

        <p class="contact-form__field[% if errors.subject %] contact-form__field--error[% endif %]">
            <label class="contact-form__label" for="oli-contact-subject">Sujet</label>
            <input class="contact-form__input" type="text" id="oli-contact-subject" name="subject" maxlength="150">
            [% if errors.subject %]<span class="contact-form__error">Sujet trop long (max 150 caractères).</span>[% endif %]
        </p>

        <p class="contact-form__field[% if errors.message %] contact-form__field--error[% endif %]">
            <label class="contact-form__label" for="oli-contact-message">Message <span aria-hidden="true">*</span></label>
            <textarea class="contact-form__textarea" id="oli-contact-message" name="message" rows="6" required minlength="10" maxlength="5000"></textarea>
            [% if errors.message %]<span class="contact-form__error">Message requis (10 à 5000 caractères).</span>[% endif %]
        </p>

        [% if errors.timestamp %]<p class="contact-form__error" role="alert">Soumission trop rapide — veuillez réessayer.</p>[% endif %]

        <p class="contact-form__actions">
            <button class="contact-form__submit btn btn--primary" type="submit">Envoyer</button>
        </p>
    </form>
</div>
