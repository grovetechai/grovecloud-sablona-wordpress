# Grove WordPress — šablona Grove Cloudu

Spravovaný WordPress: PHP 8.4, databáze MariaDB a bezpečnostní opravy řešíme za tebe.

## Co se stane po nasazení

- Grove Cloud připraví databázi MariaDB a trvalý disk pro `wp-content` (média, pluginy, šablony).
- V časové ose appky najdeš odkaz na dokončení instalace. Instalaci otevře jen tenhle odkaz.
- Databázi i `wp-content` zálohujeme a kopírujeme na druhý server.

## Co hlídáme my

- **Opravy PHP a WordPressu:** když vyjde nová image (oprava PHP, WordPressu nebo systému), web přestavíme sami. Nejdřív jeden web na zkoušku, pak ostatní. Když nová verze neprojde kontrolou, běží dál ta stará.
- **PHP štít:** při kritické díře v PHP, na kterou ještě není oprava, umíme na všech webech dočasně vypnout nahrávání souborů (média, přílohy formulářů), aby se útok k PHP vůbec nedostal.
- **Výchozí zabezpečení:** vypnuté XML-RPC, žádné PHP v nahraných souborech, skrytá verze, editor souborů v administraci vypnutý, pluginy a šablony se aktualizují samy.

## Co je tvoje

Obsah, pluginy a vzhled. Když plugin web rozbije, vrátíme zálohu a řekneme, který to byl.

## Omezení

- WordPress sám neposílá e-maily (obnova hesla, formuláře). Nainstaluj plugin pro SMTP (např. přes svou e-mailovou službu).
- Spouštění systémových příkazů z PHP je vypnuté (`disable_functions`). Pluginy, které ho potřebují, nepoběží.
