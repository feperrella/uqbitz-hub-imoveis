=== Portal Imoveis - Feed XML (OpenNavent) ===
Contributors: feperrella
Tags: real-estate, xml, feed, property, opennavent
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 3.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates an OpenNavent XML feed to sync WordPress property listings with real estate portals (ImovelWeb, Wimoveis, Casa Mineira).

== Description ==

Portal Imóveis is a WordPress plugin that automatically generates an XML feed in the OpenNavent format to synchronize property listings with major Brazilian real estate portals.

**Supported portals:**

* ImovelWeb
* Wimoveis
* Casa Mineira

**Key features:**

* Automatic XML feed at `/wp-json/portalimoveis/v1/feed`
* Custom Post Type "Imóvel" with full ACF field support
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

1. Upload the `portal-imoveis` folder to `/wp-content/plugins/`
2. Activate the plugin in the WordPress dashboard
3. Go to **Portal Imóveis → Settings** and fill in:
   * Real estate agency code (provided by the portal)
   * Contact email, name, and phone
4. Add property listings with all required fields filled in
5. Go to **Portal Imóveis → Overview** to check feed status
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
* Advanced Custom Fields PRO plugin installed and active

= Where do I find the feed URL? =

Go to **Portal Imóveis → Overview** in the WordPress dashboard. The URL is displayed at the top, in the format: `https://yoursite.com/wp-json/portalimoveis/v1/feed`

= Why is a property not showing in the feed? =

Properties with incomplete required fields are automatically excluded. Go to **Portal Imóveis → Overview** to see the list of issues for each property.

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

= 3.2.0 =
* Fixed: HTML output escaping on all admin pages (esc_html, esc_attr, esc_url)
* Fixed: Input sanitization via register_setting() callback
* Fixed: readme.txt included in plugin folder
* Updated: WordPress Plugin Check compliant

= 3.1.0 =
* Added: Expanded validation — IPTU, property age, condo fee (conditional), full address required
* Changed: Optimized layout — infrastructure, gallery, floor plans, video at full width
* Changed: Infrastructure checkbox layout set to horizontal
* Changed: "Portal Imóveis" menu repositioned below the Imóveis CPT

= 3.0.0 =
* Added: Admin dashboard with 3 pages: Overview, Settings, Field Mapping
* Added: Required field validation in XML feed
* Changed: Plugin rebranded from client-specific to generic "Portal Imóveis"
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

= 3.2.0 =
Security improvements: output escaping and input sanitization. Recommended update.

= 3.0.0 =
Feed URL changed to `/wp-json/portalimoveis/v1/feed`. Update the URL in your portal.
