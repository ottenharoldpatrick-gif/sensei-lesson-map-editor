Sensei Lesson Grid (Admin-Centric)

Overzicht
Sensei Lesson Grid is een WordPress-plugin waarmee je via een centrale grid‑editor lesmodules en tiles in vijf kolommen kunt opbouwen. De plugin biedt een shortcode [lesson_grid slug="..."] en integreert met Sensei voor voortgangscontrole en het blokkeren van lessen op basis van vereisten of inlogstatus

Belangrijkste functies
Custom Post Type “Lesson Grid” voor het beheren van grids in de WordPress‑admin, inclusief drag‑and‑drop modules en tiles

Shortcode [lesson_grid slug="jouw-grid-slug"] om een grid in pagina’s of berichten te plaatsen

Sensei‑integratie om lesvoortgang te controleren en toegang te blokkeren wanneer lessen nog niet voltooid zijn

Helperfunctie slge_grid_by_slug() voor gebruik in thema‑templates

Installatie
Upload de map sensei-lesson-map-editor naar /wp-content/plugins/.

Activeer de plugin via Plugins → Geïnstalleerde plugins in het WordPress‑dashboard.

Zorg dat de Sensei‑plugin is geïnstalleerd en geactiveerd voor voortgangsvergrendeling.

Gebruik
Maak een nieuw grid via Lesson Grids → Add New.

Voeg modules en tiles toe via de drag‑and‑drop editor.

Plaats de grid op een pagina of bericht met de shortcode:

[lesson_grid slug="mijn-grid-slug"]
Optioneel kun je de helperfunctie slge_grid_by_slug('mijn-grid-slug') gebruiken in PHP‑templates.

Ondersteunende bestandsstructuur
assets/admin.js – JavaScript voor de backend grid‑editor.

assets/admin.css – Stijlen voor de administratieomgeving.

assets/frontend.css – Basisweergave van de grid aan de voorkant.

assets/*.png – Voorbeeld- en lock‑icoonafbeeldingen.

Licentie
Deze plugin is ontwikkeld door Harold Otten en vereist minimaal WordPress 6.0 en PHP 7.4. De plugin is getest tot WordPress versie 6.8.2.
