=== UQBITZ Hub de Integracao Imobiliaria ===
Contributors: feperrella
Tags: real-estate, xml, feed, property, opennavent
Requires at least: 6.5
Tested up to: 7.0.2
Stable tag: 3.4.8
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).

== Description ==

UQBITZ Hub de Integração Imobiliária is a WordPress plugin that automatically generates an XML feed in the OpenNavent format to synchronize property listings with major Brazilian real estate portals.

**Supported portals:**

* ImovelWeb
* Wimoveis
* Casa Mineira

**Key features:**

* Automatic XML feed at `/wp-json/uqbhi/v1/feed`
* Custom Post Type "Imóvel" with full ACF (Advanced Custom Fields) support
* Taxonomies: Property Type, Purpose, City & Neighborhood
* Required field validation — incomplete listings are excluded from the feed
* Admin dashboard with feed status, instructions, and technical field mapping
* Automatic amenity and infrastructure mapping to Navent IDs
* YouTube video support (automatic code extraction)
* Floor plan gallery with custom titles
* Auto-fill address from ZIP code (via ViaCEP API)
* Complete property type/subtype mapping to the Navent API

**Validated required fields:**

* Title (min. 5 characters)
* Description (min. 50 characters)
* Price (sale or rental)
* Photo gallery (min. 5 images)
* Type and Purpose
* Full address (ZIP, street, neighborhood, city, state)
* Private area (m²)
* Property tax (IPTU)
* Property age
* Condo fee (required for apartments and gated communities)

== Installation ==

1. Upload the `uqbitz-hub-imoveis` folder to `/wp-content/plugins/`
2. Activate the plugin in the WordPress dashboard
3. Go to **Hub Imóveis → Settings** and fill in:
   * Real estate agency code (provided by the portal)
   * Contact email, name, and phone
4. Add property listings with all required fields filled in
5. Go to **Hub Imóveis → Overview** to check feed status
6. Copy the feed URL and register it in the desired portal

**Portal setup (ImovelWeb):**

1. Log in to the ImovelWeb dashboard
2. Go to **Ad Integration → XML**
3. Paste the feed URL
4. In "Integrator Name", enter **UQBITZ**
5. Save

== Frequently Asked Questions ==

= What are the requirements? =

* WordPress 6.0 or higher
* PHP 8.0 or higher
* Advanced Custom Fields (free or Pro) installed and active

= Where do I find the feed URL? =

Go to **Hub Imóveis → Overview** in the WordPress dashboard. The URL is displayed at the top, in the format: `https://yoursite.com/wp-json/uqbhi/v1/feed`

= Why is a property not showing in the feed? =

Properties with incomplete required fields are automatically excluded. Go to **Hub Imóveis → Overview** to see the list of issues for each property.

= Can I use this on multiple sites? =

Yes. Each plugin installation generates its own independent feed. Just install, configure the agency code, and add properties.

= How does the ZIP code auto-fill work? =

When you enter a ZIP code while editing a property, the plugin queries the ViaCEP API and automatically fills in street, neighborhood, city, and state.

= Do I need an API key from the portal? =

No. The XML feed is a public URL consumed by the portal. You only need the agency code provided by the portal.

== Screenshots ==

1. Overview dashboard — feed status with property list and pending issues
2. Settings — agency code and contact information
3. Field mapping — technical ACF to XML field reference table
4. Property editing — organized ACF fields

== Changelog ==

= 3.4.8 =
* Fixed: ACF Pro galleries read as empty in the feed and in the editor, so every property was emitted without the mandatory `<multimidia>` block and ImovelWeb finalized all ads on each sync. The native-gallery fallback filters (`acf/load_value`/`acf/format_value` for `galeria_de_imagens` and `plantas`) were registered at plugin load — before ACF Pro registers its `gallery` field type — so `uqbhi_has_acf_pro_gallery()` wrongly returned false and the filters stayed active alongside ACF Pro, corrupting the gallery value (attachment IDs turned into arrays, and ACF Pro's `format_value` then returned an empty gallery). The fallback filter registration is now deferred to `acf/init`, and the feed and validation read galleries through `uqbhi_get_gallery_items()`, which falls back to the raw post meta whenever ACF yields nothing. No image data was lost — the attachment IDs were intact in post meta the whole time

= 3.4.7 =
* Changed: declared WordPress compatibility bumped to 7.0.2 (Tested up to). No functional changes

= 3.4.6 =
* Fixed: portal error "o imóvel não tem uma cidade válida / localização válida" — the state was hardcoded to `São Paulo` in `<localidade>`, ignoring the ACF `estado` field. The real state is now used, with UF (`PR`) normalized to the full name (`Paraná`)
* Fixed: `<precos>` now respects `uqbhi_finalidade` — a sale-only property with a leftover `rent_price` no longer publishes an unintended rental ad
* Fixed: masked prices (`4.350.000,00`) collapsed to `4` through `intval()`, advertising a multi-million property for a few reais. They now fail validation with a message explaining the expected format
* Fixed: `uqbhi_get_tipo()` fell back to a hardcoded Apartamento (2/1) without logging when the first term (alphabetical) carried no OpenNavent mapping, exporting the property under the wrong category. It now scans every assigned term and uses the first that resolves
* Fixed: the mandatory-condo rule never fired for Casa de Condomínio — validation looked for the slug `casa-de-condominio`, the seed creates `casa-condominio`
* Hardened `dataModificacao` against the portal error "a data do XML é anterior à mudança". The root cause is not confirmed — it depends on data only the portal support and the client server hold — so this release closes every path by which the plugin could contribute: the feed was cacheable (`Cache-Control: public, max-age=3600`) and now sends `no-cache`/`no-store`, sets `DONOTCACHEPAGE` before discarding output buffers and fires `litespeed_control_set_nocache`; the timestamp is formatted with `sprintf('%.0f')` instead of `round()`, immune to a low `precision` ini and to 32-bit int overflow; the emitted value is monotonic and never moves backwards; and a canary logs any non-numeric value
* Fixed: `<localidade>` is no longer assembled with `array_filter`, which shifted the `Bairro,Cidade,Estado,País` positions when a part was empty; commas inside a part are stripped for the same reason
* Fixed: zero prices are no longer sent — OpenNavent does not accept `<quantidade>0</quantidade>` in Brazil. Zero remains valid for parking, bathrooms, IPTU, age and condo fee
* Fixed: `<titulo>` ran through `esc_html()` inside CDATA, publishing escaped entities (`&amp;`) on the portal
* Added: "Forçar atualização" setting — makes the feed win over edits made directly in the portal panel. Tunable via the `uqbhi_data_modificacao_margem` filter
* Added: validation for invalid state and for titles shorter than 10 characters (the OpenNavent minimum; the plugin previously accepted 5)

= 3.4.5 =
* Added: Elementor Dynamic Tags integration — exposes the imóvel image gallery and floor plans as a "Hub Imóveis" group inside the Elementor variable picker, so editors can bind galleries on widgets without ACF Pro
* Added: ACF-style format value filters in the gallery fallback (`acf/format_value/name=galeria_de_imagens` and `…/plantas`) — third-party integrations that read these fields via ACF now receive the same array-of-attachment-objects shape that ACF Pro emits

= 3.4.4 =
* Fixed: zero values (e.g. 0 parking spaces, 0 years old) were being treated as empty by the feed and validation. New `uqbhi_has_value()` helper distinguishes meaningful zero from empty/null/blank string

= 3.4.3 =
* Fixed: image and floor plan galleries did not render in the editor when only ACF free was installed (the `gallery` field type is Pro-only). The plugin now auto-detects the absence of ACF Pro and registers native WordPress metaboxes with `wp.media` and drag-to-reorder as a fallback
* Changed: seed `uqbhi_finalidade` now aligns with the OpenNavent operation enum — only `Venda` (VENTA) and `Aluguel` (ALQUILER); `Temporada` and `Repasse` removed
* Changed: `uqbhi_get_tipo` and `uqbhi_get_operacao` now read OpenNavent IDs from term meta with ancestor inheritance fallback for user-created custom terms
* Changed: removed the ~200-line hardcoded slug→ID map in `helpers.php`; single source of truth is now the term meta
* Added: term meta on seeded `uqbhi_tipo` terms — `uqbhi_id_tipo` and `uqbhi_id_subtipo` embed the OpenNavent numeric IDs directly on each term
* Added: term meta on seeded `uqbhi_finalidade` terms — `uqbhi_opennavent` carries the API operation code (`VENTA` / `ALQUILER`)
* Added: Spanish (`uqbhi_name_es`) and English (`uqbhi_name_en`) translations on every seeded term
* Added: one-time legacy migration backfills OpenNavent meta on pre-3.4.3 custom terms using the old slug substring match — existing installs keep emitting the same IDs

= 3.4.2 =
* Added: automatic seeding of the official `uqbhi_tipo` and `uqbhi_finalidade` terms on activation and versioned `admin_init`

= 3.4.1 =
* Changed: WordPress Coding Standards compliance — tabs, docblocks, brace style across all files

= 3.4.0 =
* Refactor: Split single-file plugin (1881 lines) into 6 modular files under `includes/` (SOLID/KISS)
* Added: ACF field `complemento` now registered via code
* Fixed: Infrastructure field name casing (`Infraestrutura` → `infraestrutura`) — items were not loading in XML feed
* Changed: Admin code only loads on dashboard (`is_admin()`)

= 3.3.0 =
* Changed: Uniform prefix `uqbhi_` for all functions, constants, options, CPT, and taxonomies
* Changed: CPT slug `imovel` → `uqbhi_imovel`; taxonomies `tipo` → `uqbhi_tipo`, `finalidade` → `uqbhi_finalidade`, `cidade-e-bairro` → `uqbhi_cidadebairro`
* Changed: REST namespace `portalimoveis/v1` → `uqbhi/v1`
* Fixed: Feed URL uses `rest_url()` instead of hardcoded `home_url('/wp-json/...')`
* Fixed: All admin page slugs prefixed (`uqbhi-portal`, `uqbhi-settings`, `uqbhi-mapping`)

= 3.2.0 =
* Fixed: HTML output escaping on all admin pages (esc_html, esc_attr, esc_url)
* Fixed: Input sanitization via register_setting() callback
* Fixed: readme.txt included in plugin folder
* Updated: WordPress Plugin Check compliant

= 3.1.0 =
* Added: Expanded validation — IPTU, property age, condo fee (conditional), full address required
* Changed: Optimized layout — infrastructure, gallery, floor plans, video at full width
* Changed: Infrastructure checkbox layout set to horizontal
* Changed: "Hub Imóveis" menu repositioned below the Imóveis CPT

= 3.0.0 =
* Added: Admin dashboard with 3 pages: Overview, Settings, Field Mapping
* Added: Required field validation in XML feed
* Changed: Plugin rebranded from client-specific to generic "UQBITZ Hub de Integração Imobiliária"
* Changed: REST API namespace changed to `portalimoveis/v1`

= 2.8.0 =
* Added: YouTube video field with automatic code extraction
* Added: Floor plan gallery with custom titles

= 2.7.0 =
* Added: IPTU, condo fee, and property age in XML characteristics
* Added: Amenity mapping to Navent AREA_PRIVATIVA IDs
* Added: Infrastructure mapping to Navent AREAS_COMUNS IDs

= 2.5.0 =
* Added: 82 Navent characteristic mappings (numeric IDs to Portuguese labels)

= 2.4.0 =
* Added: CPT and taxonomies registered via plugin code
* Added: Complete type hierarchy (5 types, 40 subtypes)

= 2.0.0 =
* Initial release: single-file plugin rewrite with REST API XML feed

== Upgrade Notice ==

= 3.4.8 =
Critical fix: ACF Pro galleries were read as empty, so the feed shipped every property without photos and ImovelWeb finalized all ads. Update immediately to restore the feed. No image data was lost.

= 3.4.7 =
Compatibility bump for WordPress 7.0.2. No functional changes since 3.4.6.

= 3.4.6 =
Fixes ads rejected by ImovelWeb for an invalid city/location — the state was hardcoded to São Paulo instead of using the property's own — and stops sending zero prices, which Brazil does not accept. Also hardens `dataModificacao` against every plugin-side cause of "a data do XML é anterior à mudança": the feed is no longer cacheable and the timestamp is immune to php.ini `precision` and 32-bit overflow.

= 3.4.5 =
Adds Elementor Dynamic Tags for the imóvel image gallery and floor plans, plus ACF-style format-value filters so external integrations consume the fallback gallery exactly like ACF Pro.

= 3.4.4 =
Fixes zero values (e.g. 0 parking, 0 years old) being treated as empty in feed output and validation.

= 3.4.3 =
Fixes missing image/floor plan galleries when only ACF free is installed (native fallback). Finalidade seed aligned with OpenNavent (Venda/Aluguel only). Terms now carry OpenNavent IDs and ES/EN translations as term meta. Existing installs re-seed automatically and run a one-time legacy migration to preserve OpenNavent IDs on custom terms — no change in the XML output for existing properties.

= 3.4.2 =
Default taxonomy terms are now seeded automatically on activation.

= 3.4.1 =
Code formatting only — no functional changes.

= 3.4.0 =
Refactored plugin structure into modular files. Fixed infrastructure items not appearing in XML feed.

= 3.3.0 =
Breaking: CPT, taxonomy, and REST namespace renamed with `uqbhi_` prefix. Feed URL changed to `/wp-json/uqbhi/v1/feed`. Update the URL in your portal.

= 3.2.0 =
Security improvements: output escaping and input sanitization. Recommended update.

= 3.0.0 =
Feed URL changed to `/wp-json/uqbhi/v1/feed`. Update the URL in your portal.
