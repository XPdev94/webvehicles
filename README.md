# WebVehicles

Aplicació interna d’OSG per registrar l’ús de vehicles, completar revisions i comunicar incidències. La versió actual s’obre a [`new/index.php`](new/index.php). Els scripts de la versió anterior són a l’arrel del repositori.

L’[informe de modernització en PDF](informe-modernitzacio-webvehicles.pdf) documenta els canvis de codi i d’interfície i inclou una comparativa visual del checklist.

## Requisits

- PHP 8.0 o superior amb l’extensió `pdo_mysql`.
- Accés a una base de dades MySQL amb l’esquema i les dades de WebVehicles. Els usuaris, els vehicles i les preguntes es llegeixen d’aquesta base de dades.
- Permís d’escriptura del procés PHP al directori `fotos/` si s’han de pujar imatges. El límit de l’aplicació és de 10 MB per imatge. Per admetre fitxers fins a aquest límit, cal que `upload_max_filesize` sigui com a mínim de 10 MB i que `post_max_size` deixi marge per a la resta de la petició.

## Connexió a MySQL

Defineix aquestes variables a l’entorn del procés PHP.

| Variable | Contingut |
| --- | --- |
| `WEBVEHICLES_DSN` | Cadena de connexió PDO, per exemple `mysql:host=localhost;dbname=webvehicles;charset=utf8mb4`. |
| `WEBVEHICLES_DB_USER` | Usuari MySQL. |
| `WEBVEHICLES_DB_PASSWORD` | Contrasenya de l’usuari MySQL. |

Quan `WEBVEHICLES_DSN` és buit o absent, [`new/database.php`](new/database.php) llegeix els valors literals de `Connections/LLUIS.php` sense executar el fitxer. La connexió per variables d’entorn utilitza els tres valors de la taula.

## Execució local

Des de l’arrel del repositori i amb la connexió configurada, executa aquesta ordre.

```sh
php -S 127.0.0.1:8000
```

Obre [http://127.0.0.1:8000/new/](http://127.0.0.1:8000/new/) i inicia la sessió amb un compte de la base de dades configurada.

## Fluxos principals

1. **Cerca i alta.** Cerca un vehicle per matrícula, codi o descripció, o reutilitza l’últim vehicle. Selecciona el tipus de revisió i indica el quilometratge, obligatori excepte per registrar una incidència.
2. **Revisió habitual.** Respon les preguntes i indica la caducitat als elements que la requereixen. Pots corregir les respostes mentre el checklist és obert, afegir un comentari opcional i reprendre la revisió des de l’historial. En completar-la s’obre el pas de fotos.
3. **Incidència o revisió directa.** En una incidència, indica la categoria i descriu què ha passat abans d’arribar a les fotos. Una revisió directa passa de l’alta a la confirmació.
4. **Fotos i confirmació.** Les fotos són opcionals. Pots afegir una imatge i continuar cap a la confirmació.
5. **Canvi de treballador.** Els comptes supervisors poden seleccionar un altre treballador de la seva empresa.

El checklist mostra «Sí» en verd i «No» en vermell quan estan seleccionats. Les dates vençudes es marquen en vermell, i les que caduquen entre avui i els pròxims 30 dies, en groc. Les preguntes conegudes es mostren en català i castellà i s’agrupen per categories. Les altres conserven el text original a «Altres elements».

## Estructura

| Fitxer | Responsabilitat |
| --- | --- |
| [`new/index.php`](new/index.php) | Punt d’entrada i comprovació de la versió de PHP. |
| [`new/app.php`](new/app.php) | Sessió, rutes, validació de formularis i redireccions. |
| [`new/database.php`](new/database.php) | Connexió PDO i consultes preparades. |
| [`new/fleet.php`](new/fleet.php) | Operacions de vehicles, registres, checklist i fotos. |
| [`new/views.php`](new/views.php) | Pantalles, formularis i etiquetes bilingües del checklist. |
| [`new/style.css`](new/style.css) | Estils de la interfície i adaptació a mòbil. |

## Comprovació de sintaxi

Executa aquesta ordre en PowerShell des de l’arrel.

```powershell
Get-ChildItem new -Filter '*.php' | ForEach-Object { php -l $_.FullName }
```
