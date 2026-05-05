[# Formulaire de la metabox 'Détails de l'événement'.
   Variables: startDate, endDate, location, address, flyerUrl, registrationUrl, price (string).
#]
<div class="oli-event-metabox">
    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="startDate">Date de début</label>
        <input class="oli-event-metabox__input"
               type="datetime-local"
               id="startDate"
               name="startDate"
               value="[[ startDate ]]">
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="endDate">Date de fin</label>
        <input class="oli-event-metabox__input"
               type="datetime-local"
               id="endDate"
               name="endDate"
               value="[[ endDate ]]">
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="location">Lieu</label>
        <input class="oli-event-metabox__input"
               type="text"
               id="location"
               name="location"
               value="[[ location ]]">
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="address">Adresse</label>
        <textarea class="oli-event-metabox__textarea"
                  id="address"
                  name="address"
                  rows="3">[[ address ]]</textarea>
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="flyerUrl">URL du flyer</label>
        <input class="oli-event-metabox__input"
               type="url"
               id="flyerUrl"
               name="flyerUrl"
               value="[[ flyerUrl ]]">
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="registrationUrl">URL d'inscription</label>
        <input class="oli-event-metabox__input"
               type="url"
               id="registrationUrl"
               name="registrationUrl"
               value="[[ registrationUrl ]]">
    </p>

    <p class="oli-event-metabox__field">
        <label class="oli-event-metabox__label" for="price">Tarif</label>
        <input class="oli-event-metabox__input"
               type="text"
               id="price"
               name="price"
               value="[[ price ]]">
    </p>
</div>
