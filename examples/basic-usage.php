<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_mainmenu PrestaShop module.
 *
 * ps_mainmenu renders the top navigation menu on the storefront. Menu items
 * can be categories, CMS pages, manufacturers, suppliers, or custom links.
 * Supports multi-level dropdowns with configurable depth.
 *
 * This file documents common usage patterns.
 */

// --- The module renders automatically in the header ---
// Hook: displayTop (main navigation bar)
// PrestaShop dispatches this hook automatically.

// --- Widget invocation in Smarty/Twig template ---
// {widget name="ps_mainmenu" hook="displayTop"}

// --- Back Office configuration ---
// Modules > Main Menu:
//   - Add menu items via drag-and-drop interface:
//     - Product categories (with sub-categories as dropdowns)
//     - CMS pages
//     - Brands/manufacturers
//     - Suppliers
//     - Custom links with label and URL
//   - Set maximum depth of dropdown levels
//   - Reorder items at each level

// --- Querying the current category from context ---
// In hooks or controllers, determine which menu item is active:
//
// $context    = Context::getContext();
// $controller = $context->controller;
//
// if ($controller instanceof CategoryController) {
//     $activeCategoryId = $controller->getCategory()->id;
// }

// --- Generating category links ---
// $link        = Context::getContext()->link;
// $categoryUrl = $link->getCategoryLink(3, 'women'); // ID + rewrite
// echo $categoryUrl; // https://shop.com/en/3-women

// --- Hook: actionObjectCategoryUpdateAfter ---
// Fires when a category is updated. The module clears its menu cache:
// (No custom code needed — handled internally.)

// --- Template override ---
// themes/{theme}/modules/ps_mainmenu/views/templates/hook/ps_mainmenu.tpl
