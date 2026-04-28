# MindBridge Demo Importer

**Contributors:** ZealousWeb  
**Tags:** demo-importer, mindbridge, one-click-demo-import  
**Requires at least:** 5.8  
**Tested up to:** 6.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## Description

The MindBridge Demo Importer is a specialized bridge plugin designed to integrate with the popular "One Click Demo Import" (OCDI) plugin for the MindBridge WordPress theme. It streamlines the onboarding process by automatically setting up:

* **Demo Content**: Pages, Posts, and custom post types (Templates).
* **Menus**: Responsive navigation for header and footer.
* **Customizer Settings**: Theme-specific styling, typography, and layout options.
* **Elementor Global Styles**: Site-wide colors and fonts configured via the Elementor Kit.
* **Fluent Forms**: Pre-configured contact and subscription forms correctly assigned to widgets.
* **Branding**: Automatic assignment of logos, favicons, and fallback images.

**Note**: This plugin is intended exclusively for use with the MindBridge theme and its required dependencies.

## Installation

1. Ensure the **MindBridge** theme is installed and active.
2. Install and activate the **One Click Demo Import** plugin from the WordPress repository.
3. Upload the `mindbridge-demo-importer` folder to your `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Navigate to **Appearance > Import Demo Data**.
6. Follow the on-screen instructions to import the "MindBridge Main Demo".

## Frequently Asked Questions

### Does this plugin delete existing content?
Yes. To ensure a perfect replication of the demo, this plugin wipes existing posts, pages, media, and Elementor kit settings before importing fresh demo data. Always back up your site before importing if you have existing content you wish to keep.

### Which theme is this for?
This importer is strictly for the MindBridge WordPress theme.

## Changelog

### 1.0.0
* Initial Release for ThemeForest.
* Added support for consolidated branding and fallback image assignment.
* Improved Fluent Form integration for footer widgets.
* Optimized Elementor style synchronization.
