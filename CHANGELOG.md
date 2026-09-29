# Changelog

## 0.9.0 - 2026-09-21

- Uitgavencategorieën onderscheiden voortaan zakelijke kosten, loonkosten, privéopnamen en balansbewegingen.
- Een afzonderlijk btw-aftrekpercentage bepaalt de voorbelasting; niet-aftrekbare btw wordt onderdeel van de resultaatkosten.
- Bestaande categorieën zijn vanuit de uitgavenflow herclassificeerbaar via declaratieve Flexgrid-AJAX.
- Uitgavenoverzicht, dashboard en AdminReport gebruiken dezelfde boekhoudkundige definitie.
- Frontenduitleg maakt voor niet-boekhouders zichtbaar welke betalingen wel en niet het resultaat beïnvloeden.

## 0.8.0 - 2026-09-21

- Optionele AdminReport-provider toegevoegd voor netto kosten, geregistreerde voorbelasting, kosten per categorie en recente uitgaven.
- Alle bijdragen zijn tenant- en valutagebonden en blijven beperkt tot `Integration/Report`.

## 0.7.1 - 2026-09-21

- Dashboardbijdrage levert expliciete presentatiemetadata voor uitgaveninvoer en ontbrekende bewijsstukken.

## 0.7.0 - 2026-09-21

- Optionele Dashboard-provider toegevoegd voor recente uitgaven, maandtotalen, snelle invoer en ontbrekende bewijsstukken.
- De bijdrage gebruikt uitsluitend tenantgebonden Expense- en Attachment-data.

## 0.6.1 - 2026-09-21

- Los uitgavenpaneel verwijderd; de controller levert nu navigatiemetadata aan het gezamenlijke AdminCore-paneel.

## 0.6.0 - 2026-09-20

- Handmatige Banking-zoekadapter toegevoegd voor tenantgebonden uitgaven op titel, leverancier en referentie.
- Resultaten tonen datum en brutobedrag; afwijkende bedragen zijn zichtbaar met waarschuwing maar niet selecteerbaar.
- Reeds aan een andere bankregel gekoppelde uitgaven worden niet opnieuw als beschikbaar doel aangeboden.
- De definitieve processor vergrendelt en weigert ook een uitgave die intussen aan een andere banktransactie is gekoppeld.

## 0.5.0 - 2026-09-20

- Banking-matchprocessor toegevoegd voor expliciet bevestigde uitgavenvoorstellen.
- De processor laadt de Expense onder een row lock en hercontroleert tenant, valuta en exact brutobedrag.
- Expense zelf wordt niet herschreven; Banking blijft eigenaar van de publieke afletterkoppeling en status.

## 0.4.0 - 2026-09-19

- Optionele Banking-kandidaatadapter toegevoegd voor negatieve banktransacties en bestaande uitgaven.
- Brutobedrag, referentie, leveranciersnaam en datumafstand leveren een transparante betrouwbaarheid en reden op.
- De adapter staat uitsluitend onder `Integration/Banking`; de Expense-kern importeert nog steeds geen Banking-code.
- Voorstellen zijn read-only en wijzigen of koppelen de uitgave nog niet.

## 0.3.0 - 2026-09-19

- Veilige bewijsstuk-upload voor PDF, JPEG, PNG en WebP tot 25 MB toegevoegd.
- Bestanden worden via Flexgrids `SecureFile` versleuteld en gesplitst opgeslagen; AdminExpense bewaart alleen de opaque referentie en controlemetadata.
- Downloadautorisatie vereist altijd een tenantgebonden Expense- én Attachment-koppeling en verifieert de SHA-256 opnieuw.
- Verwijderen wist eerst de tenantgebonden koppeling en markeert daarna het SecureFile auditbaar als verwijderd.
- Bij een mislukte metadata-transactie wordt de vooraf opgeslagen upload automatisch opgeruimd.

## 0.2.0 - 2026-09-19

- Database-schema en echte PDO-opslag na de Autowire-scan bevestigd.
- AJAX-overzicht met zoeken, categorie- en jaarfilter, sortering, paginering en exacte totalenkaarten toegevoegd.
- Aanmaak- en bewerkflow voor uitgaven en leverancierssnapshots toegevoegd.
- Categorieën kunnen vanuit de uitgavenflow tenantgebonden en idempotent worden toegevoegd.
- Updates gebruiken een row lock en behouden importidentiteit en bestaande bijlagemetadata.
- De UI gebruikt Flexgrids gedeelde `TableRenderer`, generieke adminclasses, `AjaxEvent`/`AjaxResponse` en vanilla JavaScript.

## 0.1.0 - 2026-09-19

- Zelfstandig tenantgebonden Expense-aggregate toegevoegd.
- Configureerbare `ExpenseCategory`, versioned `SupplierSnapshot` en veilige `ExpenseAttachment`-metadata toegevoegd.
- Bedragen gebruiken AdminCore `Money` in signed minor units; bruto is altijd exact netto plus btw.
- Idempotente `CreateExpense` en `CreateExpenseCategory` application-use-cases toegevoegd.
- Autowire-records voor uitgaven, categorieën en bijlagen met tenantgebonden indexes toegevoegd.
- Transactionele PDO-adapters, frameworkvrije tests en gereedstaande schema/PDO-integratietests toegevoegd.
