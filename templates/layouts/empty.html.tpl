[# Layout minimal de pontage. Variables attendues:
     - title (string)
     - message (string)
   Utilisé tant qu'aucun layout complet n'est disponible (cycle 1 - Plan 1).
#]
<!DOCTYPE html>
<html lang="[[ lang ]]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[[ title ]]</title>
    [[! wpHead !]]
</head>
<body class="oli-theme-bootstrap">
    <main>
        <h1>[[ title ]]</h1>
        <p>[[ message ]]</p>
    </main>
    [[! wpFooter !]]
</body>
</html>
