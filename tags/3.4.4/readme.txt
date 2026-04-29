=== UQBITZ Hub de Integracao Imobiliaria ===
Contributors: feperrella
Tags: real-estate, xml, feed, property, opennavent
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 3.4.4
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
