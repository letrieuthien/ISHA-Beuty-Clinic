<?php
/**
 * Plugin Name: ReMenu
 * Description: Reorganize the WordPress admin sidebar by drag-and-drop — reorder items, nest them under others, build custom sections, and give those sections custom icons. Lightweight, no third-party libraries.
 * Version:     1.7.0
 * Author:      MicroMedia
 * License:     GPL-2.0-or-later
 * Text Domain: remenu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REMENU_VERSION', '1.7.0' );
define( 'REMENU_OPTION', 'remenu_layout' );

/* -------------------------------------------------------------------------
 * Data model
 *
 * Stored option shape (v1.1):
 *   array(
 *     'sections' => array( 'remenu-content' => array( 'title','icon','icon_url' ) ),
 *     'tree'     => array( array( 'slug' => 'edit.php', 'children' => array(
 *                            array( 'slug' => '...', 'flatten' => 1 ) ) ) ),
 *   )
 * Legacy v1.0 ( 'custom_sections' + 'moves' ) is migrated transparently.
 * ---------------------------------------------------------------------- */

function remenu_get_layout() {
	$raw = get_option( REMENU_OPTION, array() );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	// Sections.
	$sections = array();
	if ( isset( $raw['sections'] ) && is_array( $raw['sections'] ) ) {
		$sections = $raw['sections'];
	} elseif ( isset( $raw['custom_sections'] ) && is_array( $raw['custom_sections'] ) ) {
		foreach ( $raw['custom_sections'] as $sec ) {
			if ( empty( $sec['slug'] ) ) {
				continue;
			}
			$sections[ $sec['slug'] ] = array(
				'title'    => isset( $sec['title'] ) ? $sec['title'] : 'Section',
				'icon'     => isset( $sec['icon'] ) ? $sec['icon'] : '',
				'icon_url' => '',
			);
		}
	}

	// Tree (null means "derive from legacy / defaults at runtime").
	$tree = ( isset( $raw['tree'] ) && is_array( $raw['tree'] ) ) ? $raw['tree'] : null;

	$legacy_moves = ( isset( $raw['moves'] ) && is_array( $raw['moves'] ) ) ? $raw['moves'] : array();

	// Accordion submenu behavior (on by default).
	$accordion = isset( $raw['accordion'] ) ? (int) $raw['accordion'] : 1;

	// Slugs hidden entirely from the live menu.
	$hidden = ( isset( $raw['hidden'] ) && is_array( $raw['hidden'] ) ) ? array_values( $raw['hidden'] ) : array();

	// Custom labels (slug => renamed title).
	$titles = ( isset( $raw['titles'] ) && is_array( $raw['titles'] ) ) ? $raw['titles'] : array();

	return array(
		'sections'     => $sections,
		'tree'         => $tree,
		'legacy_moves' => $legacy_moves,
		'accordion'    => $accordion,
		'hidden'       => $hidden,
		'titles'       => $titles,
	);
}

/* -------------------------------------------------------------------------
 * Runtime: rebuild the live admin menu from the tree
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', 'remenu_apply_layout', 9999 );

function remenu_apply_layout() {
	global $menu, $submenu;

	if ( ! isset( $GLOBALS['remenu_original_menu'] ) ) {
		$GLOBALS['remenu_original_menu']    = $menu;
		$GLOBALS['remenu_original_submenu'] = $submenu;
	}
	$GLOBALS['remenu_remap']  = array();
	$GLOBALS['remenu_groups'] = array();

	$layout    = remenu_get_layout();
	$hidden    = array_flip( $layout['hidden'] );          // slug => index, for O(1) skips
	$titles    = $layout['titles'];                        // slug => renamed label
	$orig      = remenu_original_top_items();              // ordered: slug => $menu item array
	$orig_sub  = $GLOBALS['remenu_original_submenu'];
	$sections  = $layout['sections'];
	$tree      = remenu_build_tree( $layout, $orig );
	$subitems  = remenu_collect_subitems( $orig_sub );
	$moved_sub = remenu_moved_subitems( $tree );

	$new_menu = array();
	$new_sub  = array();
	$pos      = 10;

	foreach ( $tree as $node ) {
		$slug = $node['slug'];
		$kind = isset( $node['kind'] ) ? $node['kind'] : ( isset( $sections[ $slug ] ) ? 'section' : 'item' );

		// Hidden entirely from the live menu.
		if ( isset( $hidden[ $slug ] ) ) {
			continue;
		}

		// A promoted submenu item placed at the top level.
		if ( 'subitem' === $kind ) {
			if ( ! isset( $subitems[ $slug ] ) ) {
				continue;
			}
			$info             = $subitems[ $slug ];
			$sub_title        = remenu_effective_title( isset( $titles[ $slug ] ) ? $titles[ $slug ] : '', $info['title'] );
			$new_menu[ $pos ] = array(
				$sub_title,
				$info['cap'],
				$info['url'],
				$sub_title,
				'menu-top',
				'remenu-promoted-' . sanitize_html_class( $slug ),
				'dashicons-admin-links',
			);
			remenu_remap( $slug, $info['url'] );
			$pos += 10;
			continue;
		}

		$is_section = ( 'section' === $kind );

		if ( $is_section ) {
			$meta      = $sections[ $slug ];
			$has_image = ! empty( $meta['icon_url'] );
			$icon      = $has_image
				? $meta['icon_url']
				: ( ! empty( $meta['icon'] ) ? $meta['icon'] : 'dashicons-menu-alt' );
			$classes   = 'menu-top' . ( $has_image ? ' remenu-custom-icon' : '' );
			$item      = array(
				$meta['title'],
				'read',
				$slug,
				$meta['title'],
				$classes,
				'remenu-section-' . sanitize_html_class( $slug ),
				$icon,
			);
		} else {
			if ( ! isset( $orig[ $slug ] ) ) {
				continue;
			}
			$item = $orig[ $slug ];
			if ( isset( $titles[ $slug ] ) ) {
				$item[0] = remenu_effective_title( $titles[ $slug ], isset( $item[0] ) ? $item[0] : '' );
			}
		}

		$parent = $slug;

		// Existing top-level items keep their native submenu, minus anything the
		// user has promoted/relocated out of it.
		if ( ! $is_section && isset( $orig_sub[ $slug ] ) ) {
			$kept = array();
			foreach ( $orig_sub[ $slug ] as $s ) {
				$ss = isset( $s[2] ) ? $s[2] : '';
				if ( '' !== $ss && isset( $moved_sub[ $ss ] ) ) {
					continue;
				}
				if ( '' !== $ss && isset( $hidden[ $ss ] ) ) {
					continue;
				}
				$kept[] = $s;
			}
			$new_sub[ $parent ] = $kept;
		}

		// Items / subitems dragged underneath this node.
		foreach ( $node['children'] as $child ) {
			$cslug = $child['slug'];
			$ckind = isset( $child['kind'] ) ? $child['kind'] : 'item';

			if ( isset( $hidden[ $cslug ] ) ) {
				continue;
			}

			if ( 'subitem' === $ckind ) {
				if ( ! isset( $subitems[ $cslug ] ) ) {
					continue;
				}
				$cinfo = $subitems[ $cslug ];
				if ( ! isset( $new_sub[ $parent ] ) ) {
					$new_sub[ $parent ] = array();
				}
				$new_sub[ $parent ][] = array( remenu_effective_title( isset( $titles[ $cslug ] ) ? $titles[ $cslug ] : '', $cinfo['title'] ), $cinfo['cap'], $cinfo['url'] );
				remenu_remap( $cslug, $parent );
				continue;
			}

			if ( ! isset( $orig[ $cslug ] ) ) {
				continue;
			}
			$citem  = $orig[ $cslug ];
			$ctitle = remenu_effective_title( isset( $titles[ $cslug ] ) ? $titles[ $cslug ] : '', remenu_clean_title( $citem[0] ) );
			$ccap   = isset( $citem[1] ) ? $citem[1] : 'read';

			if ( ! isset( $new_sub[ $parent ] ) ) {
				$new_sub[ $parent ] = array();
			}
			$new_sub[ $parent ][] = array( $ctitle, $ccap, $cslug );
			remenu_remap( $cslug, $parent );

			if ( ! empty( $child['flatten'] ) && isset( $orig_sub[ $cslug ] ) ) {
				$group_kids = array();
				foreach ( $orig_sub[ $cslug ] as $gc ) {
					$gs = isset( $gc[2] ) ? $gc[2] : '';
					if ( $gs === $cslug ) {
						continue; // skip the self-row duplicate
					}
					if ( '' !== $gs && isset( $moved_sub[ $gs ] ) ) {
						continue; // promoted out separately
					}
					if ( '' !== $gs && isset( $hidden[ $gs ] ) ) {
						continue; // hidden entirely
					}
					$new_sub[ $parent ][] = $gc;
					if ( '' !== $gs ) {
						remenu_remap( $gs, $parent );
						$group_kids[] = $gs;
					}
				}
				if ( $group_kids ) {
					$GLOBALS['remenu_groups'][] = array(
						'header'   => $cslug,
						'children' => $group_kids,
					);
				}
			}
		}

		// Finalize a custom section: drop if empty, else point it at its first child.
		if ( $is_section ) {
			if ( empty( $new_sub[ $parent ] ) ) {
				continue;
			}
			$first    = reset( $new_sub[ $parent ] );
			$new_slug = $first[2];
			$new_sub[ $new_slug ] = $new_sub[ $parent ];
			if ( $new_slug !== $parent ) {
				unset( $new_sub[ $parent ] );
			}
			$item[2] = $new_slug;
			$item[1] = isset( $first[1] ) ? $first[1] : 'read';
			foreach ( $GLOBALS['remenu_remap'] as $c => $p ) {
				if ( $p === $parent ) {
					$GLOBALS['remenu_remap'][ $c ] = $new_slug;
				}
			}
		}

		$new_menu[ $pos ] = $item;
		$pos += 10;
	}

	$GLOBALS['menu']    = $new_menu;
	$GLOBALS['submenu'] = $new_sub;

	remenu_register_moved_hooks();
	remenu_rebind_moved_callbacks();
}

/**
 * WordPress authorizes access to an admin page (admin.php?page=…) by deriving a
 * "hookname" from the page's PARENT menu and checking it against $_registered_pages.
 * When ReMenu relocates, flattens, or promotes a plugin page, its parent changes,
 * so the hookname WordPress computes no longer matches the one the plugin
 * registered — and access is wrongly denied even though the user is capable.
 *
 * Here we re-register the hookname for every page in its (possibly new) location.
 * This only makes pages resolvable again; the per-item capability check in
 * user_can_access_admin_page() still runs against the unchanged capability stored
 * on each menu entry, so no extra access is granted.
 */
function remenu_register_moved_hooks() {
	global $menu, $submenu, $_registered_pages;
	if ( ! function_exists( 'get_plugin_page_hookname' ) ) {
		return;
	}
	if ( ! isset( $_registered_pages ) || ! is_array( $_registered_pages ) ) {
		$_registered_pages = array();
	}

	$register = function ( $slug, $parent ) {
		if ( '' === $slug || false !== strpos( $slug, '.php' ) ) {
			return; // core file pages don't use the hookname registry
		}
		$hook = get_plugin_page_hookname( $slug, $parent );
		if ( $hook ) {
			$GLOBALS['_registered_pages'][ $hook ] = true;
		}
	};

	// 1) Pages present in the rebuilt menu, under their (possibly new) parents.
	foreach ( (array) $submenu as $parent => $items ) {
		foreach ( (array) $items as $it ) {
			$register( isset( $it[2] ) ? $it[2] : '', $parent );
		}
	}
	foreach ( (array) $menu as $it ) {
		$register( isset( $it[2] ) ? $it[2] : '', '' );
	}

	// 2) Catch-all: every page that was registered originally stays resolvable by
	// direct URL even if the layout dropped or hid it from the menu (otherwise
	// WordPress can no longer locate it and wrongly denies access). With no menu
	// parent, WordPress derives the "admin_page_{slug}" / "toplevel_page_{slug}"
	// hookname, which is what registering with an empty parent reproduces. The
	// capability check is unaffected — WordPress still consults its no-priv lists.
	$osub = isset( $GLOBALS['remenu_original_submenu'] ) ? $GLOBALS['remenu_original_submenu'] : array();
	foreach ( (array) $osub as $items ) {
		foreach ( (array) $items as $it ) {
			$register( isset( $it[2] ) ? $it[2] : '', '' );
		}
	}
	$omenu = isset( $GLOBALS['remenu_original_menu'] ) ? $GLOBALS['remenu_original_menu'] : array();
	foreach ( (array) $omenu as $it ) {
		$register( isset( $it[2] ) ? $it[2] : '', '' );
	}
}

/**
 * Compute the admin-page "hookname" for a slug under an explicit parent.
 *
 * Mirrors core get_plugin_page_hookname() for the case of a known parent, but WITHOUT
 * calling get_admin_page_parent() — which would re-derive the parent from the (already
 * rebuilt) $submenu and so could not recover a page's ORIGINAL parent once we've moved
 * it. Knowing both the original and the new hookname is exactly what the rebind below
 * needs. $admin_page_hooks maps a top-level slug to its page-type prefix.
 *
 * @param string $slug   Plugin page slug (e.g. "wpcf7-integration").
 * @param string $parent Parent slug ('' / 'admin.php' for top level).
 * @return string Hookname, e.g. "settings_page_wpcf7-integration".
 */
function remenu_page_hookname( $slug, $parent ) {
	global $admin_page_hooks;
	$name = preg_replace( '!\.php!', '', $slug );

	if ( '' === $parent || 'admin.php' === $parent || isset( $admin_page_hooks[ $slug ] ) ) {
		if ( isset( $admin_page_hooks[ $slug ] ) ) {
			$type = 'toplevel';
		} elseif ( isset( $admin_page_hooks[ $parent ] ) ) {
			$type = $admin_page_hooks[ $parent ];
		} else {
			$type = 'admin';
		}
	} elseif ( isset( $admin_page_hooks[ $parent ] ) ) {
		$type = $admin_page_hooks[ $parent ];
	} else {
		$type = 'admin';
	}

	return $type . '_page_' . $name;
}

/**
 * Re-bind moved plugin pages' render callbacks to their new hookname.
 *
 * Registering the hookname (remenu_register_moved_hooks) only satisfies the ACCESS
 * check. Rendering is separate: wp-admin/admin.php resolves the page via
 * get_plugin_page_hook( $page, 'admin.php' ), which derives the hookname from the
 * page's CURRENT parent and then requires has_action() on it. The plugin that owns the
 * page (e.g. CF7) registered its callback under the ORIGINAL hookname, so once we move
 * the page to a new parent the new hookname has no callback and WordPress dies with
 * "Cannot load <page>." even though access was granted.
 *
 * For every page now living under a different parent than originally, we add an action
 * on the NEW hookname that re-dispatches to the ORIGINAL one (both the page render and
 * its "load-" companion). No capability is touched — the access check already ran
 * against the unchanged per-item capability, so this only restores the render path.
 */
function remenu_rebind_moved_callbacks() {
	global $menu, $submenu;

	// slug => original parent ('' for a former top-level item).
	$orig_parent = array();
	$osub = isset( $GLOBALS['remenu_original_submenu'] ) ? $GLOBALS['remenu_original_submenu'] : array();
	foreach ( (array) $osub as $parent => $items ) {
		foreach ( (array) $items as $it ) {
			$s = isset( $it[2] ) ? $it[2] : '';
			if ( '' !== $s && ! isset( $orig_parent[ $s ] ) ) {
				$orig_parent[ $s ] = $parent;
			}
		}
	}
	$omenu = isset( $GLOBALS['remenu_original_menu'] ) ? $GLOBALS['remenu_original_menu'] : array();
	foreach ( (array) $omenu as $it ) {
		$s = isset( $it[2] ) ? $it[2] : '';
		if ( '' !== $s && ! isset( $orig_parent[ $s ] ) ) {
			$orig_parent[ $s ] = '';
		}
	}

	$rebind = function ( $slug, $new_parent ) use ( $orig_parent ) {
		if ( '' === $slug || false !== strpos( $slug, '.php' ) ) {
			return; // core file pages don't use plugin-page hooks
		}
		if ( ! isset( $orig_parent[ $slug ] ) ) {
			return; // not an originally-registered page (e.g. a ReMenu section)
		}
		$new_hook  = remenu_page_hookname( $slug, $new_parent );
		$orig_hook = remenu_page_hookname( $slug, $orig_parent[ $slug ] );

		if ( $new_hook === $orig_hook ) {
			return; // page is still under its original parent — nothing to do
		}
		if ( ! has_action( $orig_hook ) || has_action( $new_hook ) ) {
			return; // no original callback, or the new hook is already wired
		}

		add_action(
			$new_hook,
			static function () use ( $orig_hook ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- re-dispatching WordPress's own admin-page render hook (registered by the page's owner), not a plugin-defined hook.
				do_action( $orig_hook );
			}
		);
		add_action(
			'load-' . $new_hook,
			static function () use ( $orig_hook ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- re-dispatching WordPress's own "load-{hookname}" screen-setup hook, not a plugin-defined hook.
				do_action( 'load-' . $orig_hook );
			}
		);
	};

	foreach ( (array) $submenu as $parent => $items ) {
		foreach ( (array) $items as $it ) {
			$rebind( isset( $it[2] ) ? $it[2] : '', $parent );
		}
	}
	foreach ( (array) $menu as $it ) {
		$rebind( isset( $it[2] ) ? $it[2] : '', '' );
	}
}

function remenu_original_top_items() {
	$menu = isset( $GLOBALS['remenu_original_menu'] ) ? $GLOBALS['remenu_original_menu'] : $GLOBALS['menu'];
	if ( is_array( $menu ) ) {
		ksort( $menu ); // respect WordPress position order
	}
	$out = array();
	foreach ( (array) $menu as $item ) {
		if ( empty( $item[2] ) ) {
			continue;
		}
		if ( isset( $item[4] ) && false !== strpos( (string) $item[4], 'wp-menu-separator' ) ) {
			continue;
		}
		$out[ $item[2] ] = $item;
	}
	return $out;
}

/**
 * A native submenu item is "movable" (can be reordered, pulled up to the top
 * level, or relocated) when it's a real standalone destination. We exclude only
 * the self-row (the parent's own page) and "Add New" style actions, which aren't
 * meaningful standalone targets.
 */
function remenu_is_promotable_subitem( $slug, $parent ) {
	if ( '' === $slug || $slug === $parent ) {
		return false;
	}
	$file    = strtok( $slug, '?' ); // drop any query string
	$add_new = array( 'post-new.php', 'media-new.php', 'link-add.php', 'user-new.php', 'plugin-install.php', 'theme-install.php' );
	return ! in_array( $file, $add_new, true );
}

/**
 * Build the full, working URL for a submenu item so a promoted/relocated copy
 * still loads under its original registration.
 */
function remenu_subitem_url( $parent, $slug ) {
	if ( false !== strpos( $slug, '.php' ) ) {
		return $slug;
	}
	$base = ( false !== strpos( $parent, '.php' ) ) ? $parent : 'admin.php';
	$sep  = ( false !== strpos( $base, '?' ) ) ? '&' : '?';
	return $base . $sep . 'page=' . $slug;
}

/**
 * Map of all promotable submenu items, keyed by their (unique) slug.
 * Each value: array( title, cap, origin parent slug, full url ).
 */
function remenu_collect_subitems( $orig_sub ) {
	$map = array();
	foreach ( (array) $orig_sub as $parent => $items ) {
		foreach ( (array) $items as $s ) {
			$ss = isset( $s[2] ) ? $s[2] : '';
			if ( ! remenu_is_promotable_subitem( $ss, $parent ) || isset( $map[ $ss ] ) ) {
				continue;
			}
			$title = remenu_clean_title( $s[0] );
			if ( '' === $title ) {
				continue; // separators / icon-only entries with no visible label
			}
			$map[ $ss ] = array(
				'title'  => $title,
				'cap'    => isset( $s[1] ) ? $s[1] : 'read',
				'origin' => $parent,
				'url'    => remenu_subitem_url( $parent, $ss ),
			);
		}
	}
	return $map;
}

/** Collect every subitem slug that the tree relocates (top level or nested). */
function remenu_moved_subitems( $tree ) {
	$moved = array();
	foreach ( (array) $tree as $node ) {
		if ( isset( $node['kind'] ) && 'subitem' === $node['kind'] ) {
			$moved[ $node['slug'] ] = true;
		}
		if ( ! empty( $node['children'] ) ) {
			foreach ( $node['children'] as $c ) {
				if ( isset( $c['kind'] ) && 'subitem' === $c['kind'] ) {
					$moved[ $c['slug'] ] = true;
				}
			}
		}
	}
	return $moved;
}

function remenu_build_tree( $layout, $orig ) {
	$sections = $layout['sections'];
	$subitems = isset( $GLOBALS['remenu_original_submenu'] ) ? remenu_collect_subitems( $GLOBALS['remenu_original_submenu'] ) : array();

	if ( is_array( $layout['tree'] ) ) {
		return remenu_sanitize_tree( $layout['tree'], $orig, $sections, $subitems );
	}

	// Derive an implicit tree from legacy moves + original order.
	$children_by_parent = array();
	$moved              = array();
	foreach ( $layout['legacy_moves'] as $src => $cfg ) {
		$p = isset( $cfg['parent'] ) ? $cfg['parent'] : '';
		if ( '' === $p ) {
			continue;
		}
		$children_by_parent[ $p ][] = array(
			'slug'    => $src,
			'flatten' => ! empty( $cfg['flatten'] ) ? 1 : 0,
		);
		$moved[ $src ] = true;
	}

	$tree = array();
	foreach ( $orig as $slug => $item ) {
		if ( isset( $moved[ $slug ] ) ) {
			continue;
		}
		$tree[] = array(
			'slug'     => $slug,
			'children' => isset( $children_by_parent[ $slug ] ) ? $children_by_parent[ $slug ] : array(),
		);
	}
	foreach ( $sections as $sslug => $meta ) {
		$tree[] = array(
			'slug'     => $sslug,
			'children' => isset( $children_by_parent[ $sslug ] ) ? $children_by_parent[ $sslug ] : array(),
		);
	}
	return $tree;
}

/**
 * Validate a tree against known slugs, de-duplicate, and append any
 * items/sections that aren't represented yet (newly activated plugins,
 * freshly created sections) so nothing ever disappears.
 */
function remenu_sanitize_tree( $tree, $orig, $sections, $subitems = array() ) {
	$seen = array();
	$out  = array();

	foreach ( (array) $tree as $node ) {
		$slug = isset( $node['slug'] ) ? $node['slug'] : '';
		$kind = isset( $node['kind'] ) ? $node['kind'] : ( isset( $sections[ $slug ] ) ? 'section' : 'item' );

		if ( 'subitem' === $kind ) {
			if ( ! isset( $subitems[ $slug ] ) || isset( $seen[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;
			$out[]         = array(
				'slug'     => $slug,
				'kind'     => 'subitem',
				'origin'   => isset( $node['origin'] ) ? $node['origin'] : $subitems[ $slug ]['origin'],
				'children' => array(),
			);
			continue;
		}

		$is_section = ( 'section' === $kind ) && isset( $sections[ $slug ] );
		if ( ! $is_section && ! isset( $orig[ $slug ] ) ) {
			continue;
		}
		if ( isset( $seen[ $slug ] ) ) {
			continue;
		}
		$seen[ $slug ] = true;

		$children = array();
		$kids     = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();
		foreach ( $kids as $c ) {
			$cs    = isset( $c['slug'] ) ? $c['slug'] : '';
			$ckind = isset( $c['kind'] ) ? $c['kind'] : 'item';
			if ( isset( $seen[ $cs ] ) ) {
				continue;
			}
			if ( 'subitem' === $ckind ) {
				if ( ! isset( $subitems[ $cs ] ) ) {
					continue;
				}
				$seen[ $cs ] = true;
				$children[]  = array(
					'slug'   => $cs,
					'kind'   => 'subitem',
					'origin' => isset( $c['origin'] ) ? $c['origin'] : $subitems[ $cs ]['origin'],
				);
				continue;
			}
			if ( ! isset( $orig[ $cs ] ) ) {
				continue;
			}
			$seen[ $cs ] = true;
			$children[]  = array(
				'slug'    => $cs,
				'kind'    => 'item',
				'flatten' => ! empty( $c['flatten'] ) ? 1 : 0,
			);
		}
		$out[] = array(
			'slug'     => $slug,
			'kind'     => $is_section ? 'section' : 'item',
			'children' => $children,
		);
	}

	// Append anything not yet placed.
	foreach ( $orig as $slug => $item ) {
		if ( ! isset( $seen[ $slug ] ) ) {
			$out[]         = array( 'slug' => $slug, 'kind' => 'item', 'children' => array() );
			$seen[ $slug ] = true;
		}
	}
	foreach ( $sections as $sslug => $meta ) {
		if ( ! isset( $seen[ $sslug ] ) ) {
			$out[]          = array( 'slug' => $sslug, 'kind' => 'section', 'children' => array() );
			$seen[ $sslug ] = true;
		}
	}

	return $out;
}

function remenu_remap( $child_slug, $parent_slug ) {
	if ( ! isset( $GLOBALS['remenu_remap'] ) ) {
		$GLOBALS['remenu_remap'] = array();
	}
	$GLOBALS['remenu_remap'][ $child_slug ] = $parent_slug;
}

function remenu_clean_title( $title ) {
	$title = preg_replace( '/<span[^>]*>.*?<\/span>/is', '', (string) $title );
	return trim( wp_strip_all_tags( $title ) );
}

function remenu_sub_names( $slug, $orig_sub ) {
	if ( empty( $orig_sub[ $slug ] ) ) {
		return '';
	}
	$names = array();
	foreach ( $orig_sub[ $slug ] as $s ) {
		if ( isset( $s[2] ) && $s[2] === $slug ) {
			continue;
		}
		$names[] = remenu_clean_title( $s[0] );
	}
	return implode( ' · ', array_filter( $names ) );
}

/* -------------------------------------------------------------------------
 * Active-menu highlight fix
 * ---------------------------------------------------------------------- */

add_filter( 'parent_file', 'remenu_parent_file' );

function remenu_parent_file( $parent_file ) {
	$map     = isset( $GLOBALS['remenu_remap'] ) ? $GLOBALS['remenu_remap'] : array();
	$current = remenu_current_slug();
	return isset( $map[ $current ] ) ? $map[ $current ] : $parent_file;
}

function remenu_current_slug() {
	global $pagenow, $plugin_page, $typenow;

	if ( ! empty( $plugin_page ) ) {
		return $plugin_page;
	}

	// Read-only detection of the current admin screen, used purely to highlight the
	// matching menu item. This is navigation state taken from the URL, not a form
	// submission, so nonce verification does not apply; the value is still sanitized.
	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$slug = $pagenow;
	if ( 'edit.php' === $pagenow && ! empty( $typenow ) && 'post' !== $typenow ) {
		$slug = 'edit.php?post_type=' . $typenow;
	} elseif ( 'edit-tags.php' === $pagenow && '' !== $taxonomy ) {
		$slug = 'edit-tags.php?taxonomy=' . $taxonomy;
		if ( ! empty( $typenow ) ) {
			$slug .= '&post_type=' . $typenow;
		}
	}
	return $slug;
}

/* -------------------------------------------------------------------------
 * Settings screen (Settings -> ReMenu)
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', 'remenu_register_settings_page' );

function remenu_register_settings_page() {
	add_options_page(
		__( 'ReMenu', 'remenu' ),
		__( 'ReMenu', 'remenu' ),
		'manage_options',
		'remenu',
		'remenu_render_settings_page'
	);
}

add_action( 'admin_enqueue_scripts', 'remenu_enqueue_assets' );
function remenu_enqueue_assets( $hook ) {
	// Admin-wide chrome (icon sizing + optional accordion behaviour). The styles and
	// scripts are inline-only, so each handle is registered with a false src and the
	// payload attached via wp_add_inline_style() / wp_add_inline_script().
	wp_register_style( 'remenu-admin', false, array(), REMENU_VERSION );
	wp_enqueue_style( 'remenu-admin' );
	wp_add_inline_style( 'remenu-admin', remenu_admin_css() );

	$layout = remenu_get_layout();
	if ( ! empty( $layout['accordion'] ) ) {
		wp_register_script( 'remenu-admin', false, array(), REMENU_VERSION, true );
		wp_enqueue_script( 'remenu-admin' );
		wp_add_inline_script( 'remenu-admin', remenu_admin_js() );
	}

	// The drag-and-drop layout editor only loads on the ReMenu settings screen.
	if ( 'settings_page_remenu' === $hook ) {
		wp_enqueue_media(); // for the custom icon uploader

		wp_register_style( 'remenu-editor', false, array(), REMENU_VERSION );
		wp_enqueue_style( 'remenu-editor' );
		wp_add_inline_style( 'remenu-editor', remenu_editor_css() );

		wp_register_script( 'remenu-editor', false, array(), REMENU_VERSION, true );
		wp_enqueue_script( 'remenu-editor' );
		wp_add_inline_script( 'remenu-editor', remenu_editor_js() );
	}
}

/* -------------------------------------------------------------------------
 * Admin-wide chrome: size custom icons, and (optionally) turn submenus
 * into click-to-open accordions with the current section open by default.
 * ---------------------------------------------------------------------- */

function remenu_admin_css() {
	$layout    = remenu_get_layout();
	$accordion = ! empty( $layout['accordion'] );
	ob_start();
	?>
		/* Constrain uploaded section icons so they don't blow out the menu. */
		#adminmenu li.remenu-custom-icon div.wp-menu-image{ display:flex; align-items:center; justify-content:center; }
		#adminmenu li.remenu-custom-icon div.wp-menu-image img{ width:20px; height:20px; max-width:20px; max-height:20px; object-fit:contain; padding:0; opacity:.6; }
		#adminmenu li.remenu-custom-icon:hover div.wp-menu-image img,
		#adminmenu li.remenu-custom-icon.current div.wp-menu-image img,
		#adminmenu li.remenu-custom-icon.wp-has-current-submenu div.wp-menu-image img{ opacity:1; }

		<?php if ( $accordion ) : ?>
		@media only screen and (min-width:783px){
			body:not(.folded) #adminmenu li.remenu-accordion > a{ padding-right:34px; }

			/* When open, render the submenu inline (not as a hover flyout). Visibility,
			   positioning and the toggle button itself are all forced inline in JS so
			   nothing in WordPress's stylesheets can override them. */
			body:not(.folded) #adminmenu li.remenu-accordion.remenu-open > ul.wp-submenu{
				position:relative !important; left:auto !important; top:auto !important;
				margin:0 !important; min-width:0 !important; width:auto !important; box-shadow:none !important;
			}
			body:not(.folded) #adminmenu li.remenu-accordion > ul.wp-submenu .wp-submenu-head{ display:none !important; }
		}
		<?php endif; ?>
	<?php
	return ob_get_clean();
}

function remenu_admin_js() {
	$groups = isset( $GLOBALS['remenu_groups'] ) ? array_values( $GLOBALS['remenu_groups'] ) : array();
	ob_start();
	?>
	var REMENU_GROUPS = <?php echo wp_json_encode( $groups ); ?>;
	( function () {
		var menu = document.getElementById( 'adminmenu' );
		if ( ! menu ) { return; }

		var KEY  = 'remenuOpenMenus';   // section (top-level) open state
		var GKEY = 'remenuOpenGroups';  // brought-in group open state
		function jload( k ) { try { return JSON.parse( localStorage.getItem( k ) ) || []; } catch ( e ) { return []; } }
		function jsave( k, a ) { try { localStorage.setItem( k, JSON.stringify( a ) ); } catch ( e ) {} }

		// Accordion applies only to the desktop, expanded menu. When folded or on
		// narrow screens, WordPress's own hover fly-outs take over.
		function desktop() {
			return window.innerWidth >= 783 && ! document.body.classList.contains( 'folded' );
		}

		var INLINE_OPEN = {
			'display': 'block', 'position': 'relative', 'left': 'auto', 'top': 'auto',
			'margin': '0', 'min-width': '0', 'width': 'auto', 'box-shadow': 'none'
		};
		var LABEL = '<?php echo esc_js( __( 'Toggle submenu', 'remenu' ) ); ?>';

		// Build a styled, inline-only toggle button on a row and return it.
		// Anchored to the top of the <li>: an OPEN section's <li> is tall (its
		// submenu renders inline inside it), so centring would drop the arrow into
		// the middle of the section instead of sitting on the title row.
		function makeToggle( li, defaultH ) {
			li.style.position = 'relative';
			var a = li.querySelector( ':scope > a' );
			if ( a ) { a.style.paddingRight = '34px'; }
			var h = ( a && a.offsetHeight ) ? a.offsetHeight : ( defaultH || 34 );
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'remenu-toggle';
			btn.setAttribute( 'aria-label', LABEL );
			btn.style.cssText = 'position:absolute;right:2px;top:0;height:' + h + 'px;width:28px;' +
				'align-items:center;justify-content:center;padding:0;border:0;background:transparent;' +
				'cursor:pointer;color:#f0f0f1;z-index:20;display:none;';
			var arrow = document.createElement( 'span' );
			arrow.className = 'dashicons dashicons-arrow-down-alt2';
			arrow.style.cssText = 'width:18px;height:18px;font-size:18px;line-height:18px;transition:transform .15s ease;';
			btn.appendChild( arrow );
			li.appendChild( btn );
			return { btn: btn, arrow: arrow };
		}

		function isCurrentEl( el ) {
			return el.classList.contains( 'current' ) ||
				el.classList.contains( 'wp-has-current-submenu' ) ||
				!! el.querySelector( '.current, a[aria-current]' );
		}

		/* ---------- Top-level parents (no collapse arrow) ---------- */

		// Top-level parents (Settings, Pages, …) are left exactly as WordPress renders
		// them. The accordion chevron is added only to brought-in groups nested inside a
		// submenu (handled below) — so an arrow appears on, e.g., Yoast moved under
		// Settings, but never on Settings itself.
		var nodes = [];

		function nodeFor( li ) {
			for ( var i = 0; i < nodes.length; i++ ) { if ( nodes[ i ].li === li ) { return nodes[ i ]; } }
			return null;
		}

		function setSection( n, open ) {
			n.li.classList.toggle( 'remenu-open', open );
			n.arrow.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
			if ( ! desktop() ) {
				n.btn.style.display = 'none';
				Object.keys( INLINE_OPEN ).forEach( function ( p ) { n.sub.style.removeProperty( p ); } );
				return;
			}
			n.btn.style.display = 'flex';
			if ( open ) {
				Object.keys( INLINE_OPEN ).forEach( function ( p ) { n.sub.style.setProperty( p, INLINE_OPEN[ p ], 'important' ); } );
			} else {
				n.sub.style.setProperty( 'display', 'none', 'important' );
				[ 'position', 'left', 'top', 'margin', 'min-width', 'width', 'box-shadow' ].forEach( function ( p ) { n.sub.style.removeProperty( p ); } );
			}
		}

		function toggleSection( n ) {
			var open = ! n.li.classList.contains( 'remenu-open' );
			setSection( n, open );
			var id = n.li.id || '';
			var cur = jload( KEY ), i = cur.indexOf( id );
			if ( open ) { if ( id && i < 0 ) { cur.push( id ); } } else if ( i > -1 ) { cur.splice( i, 1 ); }
			jsave( KEY, cur );
		}

		/* ---------- Brought-in group accordions (e.g. Limit Login Attempts) ---------- */

		// Reduce a slug or href to a comparison key (the page= value, or the file name).
		function keyOf( s ) {
			s = String( s || '' );
			var m = s.match( /[?&]page=([^&]+)/ );
			if ( m ) { return decodeURIComponent( m[ 1 ] ); }
			return s.split( '?' )[ 0 ].split( '/' ).pop() || s;
		}

		function liByKey( k ) {
			var as = menu.querySelectorAll( '.wp-submenu li > a' );
			for ( var i = 0; i < as.length; i++ ) {
				if ( keyOf( as[ i ].getAttribute( 'href' ) ) === k ) { return as[ i ].parentNode; }
			}
			return null;
		}

		var groups = [];
		( REMENU_GROUPS || [] ).forEach( function ( g ) {
			if ( ! g || ! g.header || ! g.children || ! g.children.length ) { return; }
			var headerLi = liByKey( keyOf( g.header ) );
			if ( ! headerLi ) { return; }
			var childKeys = g.children.map( keyOf );
			var childLis  = [];
			menu.querySelectorAll( '.wp-submenu li > a' ).forEach( function ( a ) {
				if ( childKeys.indexOf( keyOf( a.getAttribute( 'href' ) ) ) > -1 ) {
					var li = a.parentNode;
					if ( li !== headerLi && childLis.indexOf( li ) < 0 ) { childLis.push( li ); }
				}
			} );
			if ( ! childLis.length ) { return; }
			headerLi.classList.add( 'remenu-group' );
			var t = makeToggle( headerLi, 30 );
			var grp = { headerLi: headerLi, childLis: childLis, btn: t.btn, arrow: t.arrow, key: keyOf( g.header ) };
			t.btn.__remenuGroup = grp;
			groups.push( grp );
		} );

		function groupCurrent( g ) {
			if ( isCurrentEl( g.headerLi ) ) { return true; }
			for ( var i = 0; i < g.childLis.length; i++ ) { if ( isCurrentEl( g.childLis[ i ] ) ) { return true; } }
			return false;
		}

		function setGroup( g, open ) {
			g.headerLi.classList.toggle( 'remenu-group-open', open );
			g.arrow.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
			g.childLis.forEach( function ( li ) {
				if ( ! desktop() || open ) { li.style.removeProperty( 'display' ); }
				else { li.style.setProperty( 'display', 'none', 'important' ); }
			} );
			g.btn.style.display = desktop() ? 'flex' : 'none';
		}

		function toggleGroup( g ) {
			var open = ! g.headerLi.classList.contains( 'remenu-group-open' );
			setGroup( g, open );
			var cur = jload( GKEY ), i = cur.indexOf( g.key );
			if ( open ) { if ( i < 0 ) { cur.push( g.key ); } } else if ( i > -1 ) { cur.splice( i, 1 ); }
			jsave( GKEY, cur );
		}

		/* ---------- One click handler for both, capture phase so it beats WP ---------- */

		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.remenu-toggle' ) : null;
			if ( ! btn ) { return; }
			e.preventDefault();
			e.stopPropagation();
			if ( btn.__remenuGroup ) { toggleGroup( btn.__remenuGroup ); return; }
			var li = btn.closest( 'li.menu-top' ), n = li ? nodeFor( li ) : null;
			if ( n ) { toggleSection( n ); }
		}, true );

		function applyAll() {
			var openSecs = jload( KEY ), openGrps = jload( GKEY );
			nodes.forEach( function ( n ) {
				if ( ! desktop() ) {
					n.li.classList.remove( 'remenu-open' );
					n.btn.style.display = 'none';
					Object.keys( INLINE_OPEN ).forEach( function ( p ) { n.sub.style.removeProperty( p ); } );
					return;
				}
				setSection( n, isCurrentEl( n.li ) || ( n.li.id && openSecs.indexOf( n.li.id ) > -1 ) );
			} );
			groups.forEach( function ( g ) {
				setGroup( g, desktop() && ( groupCurrent( g ) || openGrps.indexOf( g.key ) > -1 ) );
			} );
		}

		applyAll();

		// Re-sync when the menu is folded/unfolded or the window is resized.
		var collapse = document.getElementById( 'collapse-menu' );
		if ( collapse ) { collapse.addEventListener( 'click', function () { setTimeout( applyAll, 60 ); } ); }
		var t;
		window.addEventListener( 'resize', function () { clearTimeout( t ); t = setTimeout( applyAll, 150 ); } );
	} )();
	<?php
	return ob_get_clean();
}

function remenu_sanitize_slug( $value ) {
	$value = wp_unslash( (string) $value );
	return preg_replace( '/[^A-Za-z0-9_\-\.\?\=\&\/:]/', '', $value );
}

add_action( 'load-settings_page_remenu', 'remenu_handle_save' );
function remenu_handle_save() {
	if ( empty( $_POST['remenu_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'remenu_save_layout' );

	$action = sanitize_key( wp_unslash( $_POST['remenu_action'] ) );

	if ( 'reset' === $action ) {
		delete_option( REMENU_OPTION );
		remenu_save_redirect( __( 'ReMenu reset. The sidebar is back to its default layout.', 'remenu' ) );
	}
	if ( 'save' !== $action ) {
		return;
	}

	$accordion = empty( $_POST['remenu_accordion'] ) ? 0 : 1;

	// --- Hidden slugs (JSON payload: unslashed here, each value sanitized below) ---
	$hidden     = array();
	$hidden_raw = isset( $_POST['remenu_hidden'] ) ? wp_unslash( $_POST['remenu_hidden'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$hidden_dec = json_decode( $hidden_raw, true );
	if ( is_array( $hidden_dec ) ) {
		foreach ( $hidden_dec as $hs ) {
			$hs = remenu_sanitize_slug( $hs );
			if ( '' !== $hs ) {
				$hidden[ $hs ] = true;
			}
		}
		$hidden = array_keys( $hidden );
	}

	// --- Custom labels (JSON map of slug => renamed title) ---
	$titles_map = array();
	$titles_raw = isset( $_POST['remenu_titles'] ) ? wp_unslash( $_POST['remenu_titles'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$titles_dec = json_decode( $titles_raw, true );
	if ( is_array( $titles_dec ) ) {
		foreach ( $titles_dec as $tslug => $tval ) {
			$tslug = remenu_sanitize_slug( $tslug );
			$tval  = sanitize_text_field( (string) $tval );
			if ( '' !== $tslug && '' !== $tval ) {
				$titles_map[ $tslug ] = $tval;
			}
		}
	}

	// --- Sections (each field unslashed + sanitized at the point of access) ---
	$titles  = isset( $_POST['remenu_section_title'] )    ? map_deep( wp_unslash( (array) $_POST['remenu_section_title'] ), 'sanitize_text_field' ) : array();
	$icons   = isset( $_POST['remenu_section_icon'] )     ? map_deep( wp_unslash( (array) $_POST['remenu_section_icon'] ), 'sanitize_text_field' ) : array();
	$urls    = isset( $_POST['remenu_section_icon_url'] ) ? map_deep( wp_unslash( (array) $_POST['remenu_section_icon_url'] ), 'esc_url_raw' ) : array();
	$slugs   = isset( $_POST['remenu_section_slug'] )     ? map_deep( wp_unslash( (array) $_POST['remenu_section_slug'] ), 'sanitize_title' ) : array();
	$deletes = isset( $_POST['remenu_section_delete'] )   ? map_deep( wp_unslash( (array) $_POST['remenu_section_delete'] ), 'sanitize_text_field' ) : array();

	$sections = array();
	$used     = array();
	foreach ( $titles as $k => $title ) {
		if ( isset( $deletes[ $k ] ) ) {
			continue;
		}
		if ( '' === $title ) {
			continue;
		}
		$slug = ( isset( $slugs[ $k ] ) && '' !== $slugs[ $k ] ) ? $slugs[ $k ] : 'remenu-' . sanitize_title( $title );
		if ( 0 !== strpos( $slug, 'remenu-' ) ) {
			$slug = 'remenu-' . $slug;
		}
		$base = $slug;
		$n    = 2;
		while ( isset( $used[ $slug ] ) ) {
			$slug = $base . '-' . $n;
			$n++;
		}
		$used[ $slug ] = true;

		$sections[ $slug ] = array(
			'title'    => $title,
			'icon'     => isset( $icons[ $k ] ) ? $icons[ $k ] : '',
			'icon_url' => isset( $urls[ $k ] ) ? $urls[ $k ] : '',
		);
	}

	// --- Tree (from drag-and-drop) ---
	$orig      = remenu_original_top_items();
	$subitems  = remenu_collect_subitems( $GLOBALS['remenu_original_submenu'] );
	$allowed   = $orig; // slug => item
	foreach ( $sections as $sslug => $meta ) {
		$allowed[ $sslug ] = true;
	}

	$tree     = null;
	$tree_raw = isset( $_POST['remenu_tree'] ) ? wp_unslash( $_POST['remenu_tree'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$decoded  = json_decode( $tree_raw, true );

	if ( is_array( $decoded ) && ! empty( $decoded ) ) {
		$tree = array();
		$seen = array();
		foreach ( $decoded as $node ) {
			$slug = isset( $node['slug'] ) ? remenu_sanitize_slug( $node['slug'] ) : '';
			$kind = isset( $node['kind'] ) ? sanitize_key( $node['kind'] ) : 'item';
			if ( '' === $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}

			// A promoted submenu item at the top level.
			if ( 'subitem' === $kind ) {
				if ( ! isset( $subitems[ $slug ] ) ) {
					continue;
				}
				$seen[ $slug ] = true;
				$tree[]        = array(
					'slug'     => $slug,
					'kind'     => 'subitem',
					'origin'   => isset( $node['origin'] ) ? remenu_sanitize_slug( $node['origin'] ) : $subitems[ $slug ]['origin'],
					'children' => array(),
				);
				continue;
			}

			if ( ! isset( $allowed[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;
			$children      = array();
			if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
				foreach ( $node['children'] as $c ) {
					$cs    = isset( $c['slug'] ) ? remenu_sanitize_slug( $c['slug'] ) : '';
					$ckind = isset( $c['kind'] ) ? sanitize_key( $c['kind'] ) : 'item';
					if ( '' === $cs || isset( $seen[ $cs ] ) ) {
						continue;
					}
					if ( 'subitem' === $ckind ) {
						if ( ! isset( $subitems[ $cs ] ) ) {
							continue;
						}
						$seen[ $cs ] = true;
						$children[]  = array(
							'slug'   => $cs,
							'kind'   => 'subitem',
							'origin' => isset( $c['origin'] ) ? remenu_sanitize_slug( $c['origin'] ) : $subitems[ $cs ]['origin'],
						);
						continue;
					}
					if ( ! isset( $orig[ $cs ] ) ) {
						continue;
					}
					$seen[ $cs ] = true;
					$children[]  = array(
						'slug'    => $cs,
						'kind'    => 'item',
						'flatten' => ! empty( $c['flatten'] ) ? 1 : 0,
					);
				}
			}
			$tree[] = array(
				'slug'     => $slug,
				'kind'     => isset( $sections[ $slug ] ) ? 'section' : 'item',
				'children' => $children,
			);
		}
	} else {
		// No usable tree posted (e.g. JS off): keep whatever was stored.
		$existing = remenu_get_layout();
		$tree     = is_array( $existing['tree'] ) ? $existing['tree'] : null;
	}

	update_option(
		REMENU_OPTION,
		array(
			'sections'  => $sections,
			'tree'      => $tree,
			'accordion' => $accordion,
			'hidden'    => $hidden,
			'titles'    => $titles_map,
		)
	);
	remenu_save_redirect( __( 'Menu layout saved.', 'remenu' ) );
}

/**
 * Persist a one-time notice and redirect back to the settings screen (Post/Redirect/Get).
 * The rebuilt sidebar then reflects the new layout on the very next load, and a browser
 * refresh no longer re-submits the form.
 */
function remenu_save_redirect( $message ) {
	set_transient( 'remenu_notice_' . get_current_user_id(), $message, 30 );
	wp_safe_redirect( add_query_arg( 'page', 'remenu', admin_url( 'options-general.php' ) ) );
	exit;
}

function remenu_icon_markup( $icon, $is_section = false ) {
	if ( '' === $icon || 'none' === $icon || 'div' === $icon ) {
		return '<span class="remenu-icon remenu-icon-blank dashicons dashicons-' . ( $is_section ? 'menu-alt' : 'admin-generic' ) . '"></span>';
	}
	if ( 0 === strpos( $icon, 'data:' ) || preg_match( '#^https?://#', $icon ) ) {
		return '<img class="remenu-icon" src="' . esc_url( $icon ) . '" alt="" />';
	}
	if ( 0 === strpos( $icon, 'dashicons-' ) ) {
		return '<span class="remenu-icon dashicons ' . esc_attr( $icon ) . '"></span>';
	}
	return '<span class="remenu-icon remenu-icon-blank dashicons dashicons-admin-generic"></span>';
}

function remenu_nonpromotable_sub_names( $slug, $orig_sub ) {
	if ( empty( $orig_sub[ $slug ] ) ) {
		return '';
	}
	$names = array();
	foreach ( $orig_sub[ $slug ] as $s ) {
		$ss = isset( $s[2] ) ? $s[2] : '';
		if ( $ss === $slug || remenu_is_promotable_subitem( $ss, $slug ) ) {
			continue;
		}
		$names[] = remenu_clean_title( $s[0] );
	}
	return implode( ' · ', array_filter( $names ) );
}

function remenu_hide_label( $slug, $hidden ) {
	$checked = isset( $hidden[ $slug ] ) ? ' checked' : '';
	return '<label class="remenu-hide" title="' . esc_attr__( 'Hide this item from the live menu', 'remenu' ) . '">'
		. '<input type="checkbox" class="remenu-hide-cb"' . $checked . ' /> '
		. esc_html__( 'hide', 'remenu' ) . '</label>';
}

/**
 * The label to show for an item: the custom title if one is set, otherwise the
 * original. Any trailing update/count badge on the original (Plugins, Comments, …)
 * is kept, so notification bubbles survive a rename.
 */
function remenu_effective_title( $custom, $original ) {
	$custom = is_string( $custom ) ? trim( $custom ) : '';
	if ( '' === $custom ) {
		return $original;
	}
	if ( preg_match( '/(\s*<span\b.*<\/span>)\s*$/is', (string) $original, $m ) ) {
		return $custom . $m[1];
	}
	return $custom;
}

/**
 * Editor cell: the current label, a pencil to rename it inline, and a hidden input
 * holding the custom override ( '' means use the item's default title ).
 */
function remenu_label_cell( $default_title, $custom_title = '' ) {
	$custom  = is_string( $custom_title ) ? $custom_title : '';
	$display = ( '' !== $custom ) ? $custom : $default_title;
	ob_start();
	?>
	<span class="remenu-label" data-default="<?php echo esc_attr( $default_title ); ?>"><?php echo esc_html( $display ); ?></span>
	<button type="button" class="remenu-rename" title="<?php esc_attr_e( 'Rename', 'remenu' ); ?>" aria-label="<?php esc_attr_e( 'Rename', 'remenu' ); ?>"><span class="dashicons dashicons-edit"></span></button>
	<input type="text" class="remenu-title-input" value="<?php echo esc_attr( $custom ); ?>" hidden />
	<?php
	return ob_get_clean();
}

function remenu_render_subitem_chip( $slug, $info, $hidden = array(), $custom = '' ) {
	$is_hidden = isset( $hidden[ $slug ] ) ? ' remenu-hidden' : '';
	?>
	<li class="remenu-node remenu-subitem<?php echo esc_attr( $is_hidden ); ?>" draggable="true" data-slug="<?php echo esc_attr( $slug ); ?>" data-type="subitem" data-kind="subitem" data-origin="<?php echo esc_attr( $info['origin'] ); ?>">
		<div class="remenu-row">
			<span class="remenu-handle dashicons dashicons-menu"></span>
			<span class="remenu-subitem-mark dashicons dashicons-arrow-up-alt2" title="<?php esc_attr_e( 'Drag up to make this a top-level item', 'remenu' ); ?>"></span>
			<?php echo remenu_label_cell( $info['title'], $custom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="remenu-sub remenu-sub-link"><?php esc_html_e( 'link', 'remenu' ); ?></span>
			<?php echo remenu_hide_label( $slug, $hidden ); // phpcs:ignore ?>
		</div>
	</li>
	<?php
}

function remenu_render_item_child( $cslug, $orig, $orig_sub, $flatten, $hidden = array(), $custom = '' ) {
	$ctitle    = remenu_clean_title( $orig[ $cslug ][0] );
	$csub      = remenu_sub_names( $cslug, $orig_sub );
	$is_hidden = isset( $hidden[ $cslug ] ) ? ' remenu-hidden' : '';
	?>
	<li class="remenu-node remenu-child<?php echo esc_attr( $is_hidden ); ?>" draggable="true" data-slug="<?php echo esc_attr( $cslug ); ?>" data-type="item" data-kind="item">
		<div class="remenu-row">
			<span class="remenu-handle dashicons dashicons-menu"></span>
			<?php echo remenu_label_cell( $ctitle, $custom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $csub ) : ?>
				<span class="remenu-sub"><?php echo esc_html( $csub ); ?></span>
			<?php endif; ?>
			<label class="remenu-flatten">
				<input type="checkbox" class="remenu-flatten-cb" <?php checked( ! empty( $flatten ) ); ?> />
				<?php esc_html_e( 'bring sub-items', 'remenu' ); ?>
			</label>
			<?php echo remenu_hide_label( $cslug, $hidden ); // phpcs:ignore ?>
		</div>
	</li>
	<?php
}

function remenu_editor_css() {
	ob_start();
	?>
		.remenu-list{ list-style:none; margin:0; padding:0; }
		.remenu-root{ max-width:760px; }
		.remenu-node{ background:#fff; border:1px solid #dcdcde; border-radius:6px; margin:6px 0; }
		.remenu-section{ border-color:#c3c4c7; background:#f6f7f7; }
		.remenu-row{ display:flex; align-items:center; gap:8px; padding:8px 10px; }
		.remenu-handle{ cursor:grab; color:#787c82; }
		.remenu-handle:active{ cursor:grabbing; }
		.remenu-icon{ width:20px; height:20px; color:#50575e; flex:0 0 auto; }
		.remenu-icon-blank{ opacity:.45; }
		.remenu-label{ font-weight:600; }
		.remenu-rename{ background:transparent; border:0; padding:0 2px; margin:0; cursor:pointer; color:#787c82; line-height:1; display:inline-flex; align-items:center; }
		.remenu-rename:hover{ color:#2271b1; }
		.remenu-rename .dashicons{ width:16px; height:16px; font-size:16px; }
		.remenu-title-input{ font-size:13px; line-height:1.4; padding:2px 6px; min-width:180px; }
		.remenu-tag{ font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#fff; background:#2271b1; border-radius:3px; padding:1px 6px; }
		.remenu-sub{ color:#787c82; font-size:12px; margin-left:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.remenu-flatten{ margin-left:auto; font-size:12px; color:#50575e; white-space:nowrap; }
		.remenu-hide{ margin-left:auto; font-size:12px; color:#50575e; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
		.remenu-hide-cb{ margin:0; }
		.remenu-flatten ~ .remenu-hide, .remenu-sub-link ~ .remenu-hide{ margin-left:12px; }
		.remenu-hidden > .remenu-row{ opacity:.5; }
		.remenu-hidden > .remenu-row .remenu-label{ text-decoration:line-through; }
		.remenu-children{ margin:0 0 8px 30px; padding:2px 6px; border-left:2px dashed #dcdcde; min-height:14px; }
		.remenu-child{ margin:4px 0; }
		.remenu-subitem{ background:#fbfbfc; border-style:dashed; }
		.remenu-subitem .remenu-label{ font-weight:400; }
		.remenu-subitem-mark{ color:#2271b1; width:16px; height:16px; font-size:16px; flex:0 0 auto; }
		.remenu-sub-link{ margin-left:auto; font-style:italic; }
		.remenu-empty-hint{ color:#a7aaad; font-size:12px; font-style:italic; padding:6px 4px; }
		.remenu-dragging{ opacity:.45; }
		.remenu-placeholder{ height:0; border-top:2px solid #2271b1; margin:4px 0; list-style:none; }
		.remenu-icon-cell{ display:flex; align-items:center; gap:8px; }
		.remenu-icon-preview{ width:24px; height:24px; object-fit:contain; }
	<?php
	return ob_get_clean();
}

function remenu_editor_js() {
	ob_start();
	?>
	( function () {
		var root = document.getElementById( 'remenu-tree' );
		var form = document.getElementById( 'remenu-form' );
		if ( ! root ) { return; }

		/* ---- Inline rename: pencil swaps the label for a text field ---- */
		function remenuCommitRename( input ) {
			var row = input.closest( '.remenu-row' );
			if ( ! row ) { return; }
			var label = row.querySelector( '.remenu-label' );
			var btn   = row.querySelector( '.remenu-rename' );
			var li    = input.closest( 'li.remenu-node' );
			var def   = label ? ( label.getAttribute( 'data-default' ) || '' ) : '';
			var val   = input.value.trim();
			var custom = ( val === '' || val === def ) ? '' : val;
			input.value = custom;
			if ( label ) { label.textContent = ( custom !== '' ) ? custom : def; label.hidden = false; }
			if ( btn ) { btn.hidden = false; }
			input.hidden = true;
			if ( li ) { li.setAttribute( 'draggable', 'true' ); }
		}
		root.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.remenu-rename' ) : null;
			if ( ! btn ) { return; }
			e.preventDefault(); e.stopPropagation();
			var row = btn.closest( '.remenu-row' );
			var label = row.querySelector( '.remenu-label' );
			var input = row.querySelector( '.remenu-title-input' );
			var li = btn.closest( 'li.remenu-node' );
			if ( ! label || ! input ) { return; }
			input.value = label.textContent;
			label.hidden = true; btn.hidden = true; input.hidden = false;
			if ( li ) { li.setAttribute( 'draggable', 'false' ); }
			input.focus(); input.select();
		} );
		root.addEventListener( 'keydown', function ( e ) {
			if ( ! e.target.classList || ! e.target.classList.contains( 'remenu-title-input' ) ) { return; }
			if ( e.key === 'Enter' || e.key === 'Escape' ) { e.preventDefault(); remenuCommitRename( e.target ); }
		} );
		root.addEventListener( 'blur', function ( e ) {
			if ( e.target.classList && e.target.classList.contains( 'remenu-title-input' ) ) { remenuCommitRename( e.target ); }
		}, true );

		var dragged = null;
		var ph = document.createElement( 'li' );
		ph.className = 'remenu-placeholder';

		function isSection( node ) { return node.dataset.type === 'section'; }
		function canDrop( list, node ) {
			if ( node.contains( list ) ) { return false; } // can't drop into your own subtree
			if ( list.classList.contains( 'remenu-children' ) ) {
				// Sections stay at the top level. Anything else can be nested — if it
				// has its own sub-pages they ride along via "bring sub-items"
				// (flattened to one level, since menus are only two deep).
				return ! isSection( node );
			}
			return list === root;
		}
		function afterElement( list, y ) {
			var els = Array.prototype.slice.call( list.querySelectorAll( ':scope > li.remenu-node:not(.remenu-dragging)' ) );
			var res = null, closest = -Infinity;
			els.forEach( function ( el ) {
				var b = el.getBoundingClientRect();
				var off = y - ( b.top + b.height / 2 );
				if ( off < 0 && off > closest ) { closest = off; res = el; }
			} );
			return res;
		}
		function addNodeHandlers( node ) {
			if ( node.__remenuBound ) { return; }
			node.__remenuBound = true;
			node.addEventListener( 'dragstart', function ( e ) {
				if ( e.target.closest && e.target.closest( '.remenu-flatten' ) ) { e.preventDefault(); return; }
				e.stopPropagation();
				dragged = node;
				node.classList.add( 'remenu-dragging' );
				e.dataTransfer.effectAllowed = 'move';
				try { e.dataTransfer.setData( 'text/plain', node.dataset.slug || 'x' ); } catch ( _ ) {}
			} );
			node.addEventListener( 'dragend', function ( e ) {
				e.stopPropagation();
				node.classList.remove( 'remenu-dragging' );
				if ( ph.parentNode ) { ph.remove(); }
				dragged = null;
				normalize();
			} );
		}
		function bindLists() {
			document.querySelectorAll( '.remenu-list' ).forEach( function ( list ) {
				if ( list.__remenuBound ) { return; }
				list.__remenuBound = true;
				list.addEventListener( 'dragover', function ( e ) {
					if ( ! dragged || ! canDrop( list, dragged ) ) { return; }
					e.preventDefault();
					e.stopPropagation();
					var after = afterElement( list, e.clientY );
					if ( after == null ) { list.appendChild( ph ); }
					else { list.insertBefore( ph, after ); }
				} );
				list.addEventListener( 'drop', function ( e ) {
					if ( ! dragged || ! canDrop( list, dragged ) ) { return; }
					e.preventDefault();
					e.stopPropagation();
					if ( ph.parentNode ) { ph.parentNode.insertBefore( dragged, ph ); ph.remove(); }
					normalize();
				} );
			} );
		}
		function removeFlatten( node ) {
			var f = node.querySelector( ':scope > .remenu-row .remenu-flatten' );
			if ( f ) { f.remove(); }
		}
		function ensureFlatten( node ) {
			var row = node.querySelector( ':scope > .remenu-row' );
			if ( ! row || row.querySelector( '.remenu-flatten' ) ) { return; }
			var l = document.createElement( 'label' );
			l.className = 'remenu-flatten';
			var cb = document.createElement( 'input' );
			cb.type = 'checkbox';
			cb.className = 'remenu-flatten-cb';
			cb.checked = true;
			l.appendChild( cb );
			l.appendChild( document.createTextNode( ' <?php echo esc_js( __( 'bring sub-items', 'remenu' ) ); ?>' ) );
			var hideEl = row.querySelector( '.remenu-hide' );
			if ( hideEl ) { row.insertBefore( l, hideEl ); } else { row.appendChild( l ); }
		}
		function isSubitem( node ) { return node.dataset.kind === 'subitem'; }
		function normalize() {
			root.querySelectorAll( ':scope > li.remenu-node' ).forEach( function ( node ) {
				node.classList.remove( 'remenu-child' );
				if ( isSubitem( node ) ) {
					var scu = node.querySelector( ':scope > ul.remenu-children' );
					if ( scu ) { scu.remove(); }
					removeFlatten( node );
					return;
				}
				if ( ! node.querySelector( ':scope > ul.remenu-children' ) ) {
					var ul = document.createElement( 'ul' );
					ul.className = 'remenu-list remenu-children';
					node.appendChild( ul );
				}
				removeFlatten( node );
			} );
			root.querySelectorAll( 'ul.remenu-children > li.remenu-node' ).forEach( function ( child ) {
				child.classList.add( 'remenu-child' );
				var cu = child.querySelector( ':scope > ul.remenu-children' );
				if ( cu ) { cu.remove(); }
				if ( isSubitem( child ) ) { removeFlatten( child ); }
				else { ensureFlatten( child ); }
			} );
			document.querySelectorAll( 'ul.remenu-children' ).forEach( function ( ul ) {
				var hasNode = ul.querySelector( ':scope > li.remenu-node' );
				var hint = ul.querySelector( ':scope > li.remenu-empty-hint' );
				if ( ! hasNode ) {
					if ( ! hint ) {
						hint = document.createElement( 'li' );
						hint.className = 'remenu-empty-hint';
						hint.textContent = '<?php echo esc_js( __( 'Drag items here to nest them', 'remenu' ) ); ?>';
					}
					ul.appendChild( hint );
				} else if ( hint ) {
					hint.remove();
				}
			} );
			bindLists();
			root.querySelectorAll( 'li.remenu-node' ).forEach( addNodeHandlers );
		}

		root.querySelectorAll( 'li.remenu-node' ).forEach( addNodeHandlers );
		bindLists();

		// Reflect hide checkboxes visually as they're toggled.
		root.addEventListener( 'change', function ( e ) {
			if ( e.target && e.target.classList && e.target.classList.contains( 'remenu-hide-cb' ) ) {
				var node = e.target.closest( 'li.remenu-node' );
				if ( node ) { node.classList.toggle( 'remenu-hidden', e.target.checked ); }
			}
		} );

		form.addEventListener( 'submit', function () {
			var tree = [];
			root.querySelectorAll( ':scope > li.remenu-node' ).forEach( function ( node ) {
				// A promotable sub-item dragged up to the top level.
				if ( node.dataset.kind === 'subitem' ) {
					tree.push( { slug: node.dataset.slug, kind: 'subitem', origin: node.dataset.origin || '', children: [] } );
					return;
				}
				var children = [];
				node.querySelectorAll( ':scope > ul.remenu-children > li.remenu-node' ).forEach( function ( ch ) {
					if ( ch.dataset.kind === 'subitem' ) {
						// Serialize every sub-item chip in its current order — including
						// ones still under their own parent — so in-place reordering sticks.
						children.push( { slug: ch.dataset.slug, kind: 'subitem', origin: ch.dataset.origin || '' } );
						return;
					}
					var cb = ch.querySelector( '.remenu-flatten-cb' );
					children.push( { slug: ch.dataset.slug, kind: 'item', flatten: ( cb && cb.checked ) ? 1 : 0 } );
				} );
				tree.push( { slug: node.dataset.slug, kind: node.dataset.kind || 'item', children: children } );
			} );
			document.getElementById( 'remenu_tree' ).value = JSON.stringify( tree );

			var hidden = [];
			root.querySelectorAll( 'li.remenu-node' ).forEach( function ( node ) {
				var cb = node.querySelector( ':scope > .remenu-row .remenu-hide-cb' );
				if ( cb && cb.checked && node.dataset.slug ) { hidden.push( node.dataset.slug ); }
			} );
			document.getElementById( 'remenu_hidden' ).value = JSON.stringify( hidden );

			var titles = {};
			root.querySelectorAll( 'li.remenu-node' ).forEach( function ( node ) {
				var ti = node.querySelector( ':scope > .remenu-row .remenu-title-input' );
				if ( ti && ti.value.trim() !== '' && node.dataset.slug ) { titles[ node.dataset.slug ] = ti.value.trim(); }
			} );
			document.getElementById( 'remenu_titles' ).value = JSON.stringify( titles );
		} );

		/* ---- Custom sections: add row + media uploader ---- */
		var addBtn = document.getElementById( 'remenu-add-section' );
		if ( addBtn ) {
			addBtn.addEventListener( 'click', function () {
				var tbody = document.querySelector( '#remenu-sections tbody' );
				var max = -1;
				tbody.querySelectorAll( 'input[name^="remenu_section_title["]' ).forEach( function ( el ) {
					var m = el.name.match( /\[(\d+)\]/ );
					if ( m ) { max = Math.max( max, parseInt( m[1], 10 ) ); }
				} );
				var i = max + 1;
				var tr = document.createElement( 'tr' );
				tr.innerHTML =
					'<td><input type="text" name="remenu_section_title[' + i + ']" value="" placeholder="<?php echo esc_js( __( 'New section name…', 'remenu' ) ); ?>" style="width:100%;" /></td>' +
					'<td><input type="text" name="remenu_section_icon[' + i + ']" value="" placeholder="dashicons-menu-alt" style="width:100%;" /></td>' +
					'<td class="remenu-icon-cell">' +
						'<img class="remenu-icon-preview" src="" alt="" style="display:none;" />' +
						'<input type="hidden" class="remenu-icon-url" name="remenu_section_icon_url[' + i + ']" value="" />' +
						'<button type="button" class="button remenu-upload-icon"><?php echo esc_js( __( 'Upload', 'remenu' ) ); ?></button>' +
						'<button type="button" class="button-link remenu-clear-icon" style="display:none;"><?php echo esc_js( __( 'remove', 'remenu' ) ); ?></button>' +
					'</td><td></td>';
				tbody.appendChild( tr );
			} );
		}

		document.addEventListener( 'click', function ( e ) {
			var up = e.target.closest && e.target.closest( '.remenu-upload-icon' );
			if ( up ) {
				e.preventDefault();
				if ( typeof wp === 'undefined' || ! wp.media ) { return; }
				var cell = up.closest( '.remenu-icon-cell' );
				var frame = wp.media( { title: '<?php echo esc_js( __( 'Select icon image', 'remenu' ) ); ?>', multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					var url = att.url;
					if ( att.sizes ) {
						if ( att.sizes.thumbnail ) { url = att.sizes.thumbnail.url; }
						else if ( att.sizes.medium ) { url = att.sizes.medium.url; }
					}
					cell.querySelector( '.remenu-icon-url' ).value = url;
					var img = cell.querySelector( '.remenu-icon-preview' );
					img.src = url;
					img.style.display = 'inline-block';
					cell.querySelector( '.remenu-clear-icon' ).style.display = 'inline';
				} );
				frame.open();
				return;
			}
			var clr = e.target.closest && e.target.closest( '.remenu-clear-icon' );
			if ( clr ) {
				e.preventDefault();
				var c = clr.closest( '.remenu-icon-cell' );
				c.querySelector( '.remenu-icon-url' ).value = '';
				var pimg = c.querySelector( '.remenu-icon-preview' );
				pimg.src = '';
				pimg.style.display = 'none';
				clr.style.display = 'none';
			}
		} );
	} )();
	<?php
	return ob_get_clean();
}

function remenu_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$layout    = remenu_get_layout();
	$hidden    = array_flip( $layout['hidden'] );
	$orig      = remenu_original_top_items();
	$orig_sub  = isset( $GLOBALS['remenu_original_submenu'] ) ? $GLOBALS['remenu_original_submenu'] : $GLOBALS['submenu'];
	$sections  = $layout['sections'];
	$tree      = remenu_build_tree( $layout, $orig );
	$subitems  = remenu_collect_subitems( $orig_sub );
	$moved_sub = remenu_moved_subitems( $tree );
	$titles    = $layout['titles'];

	$remenu_notice = get_transient( 'remenu_notice_' . get_current_user_id() );
	if ( $remenu_notice ) {
		delete_transient( 'remenu_notice_' . get_current_user_id() );
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $remenu_notice ) );
	}
	?>
	<div class="wrap remenu-wrap">
		<h1><?php esc_html_e( 'ReMenu', 'remenu' ); ?></h1>
		<p class="description" style="max-width:820px;">
			<?php esc_html_e( 'Drag items to reorder them, or drop one onto another item or a custom section to nest it. When an item is nested, tick "bring sub-items" to carry its sub-pages along (flattened to one level — WordPress menus are only two levels deep). Most sub-pages — including core screens like Writing or Reading — appear as draggable "link" rows: drag one up to the top level to promote it, or onto another item to relocate it. Tick "hide" on any row to remove it from the live menu entirely (it stays here so you can bring it back).', 'remenu' ); ?>
		</p>

		<form method="post" action="" id="remenu-form">
			<?php wp_nonce_field( 'remenu_save_layout' ); ?>
			<input type="hidden" name="remenu_action" value="save" />
			<input type="hidden" name="remenu_tree" id="remenu_tree" value="" />
			<input type="hidden" name="remenu_hidden" id="remenu_hidden" value="" />
			<input type="hidden" name="remenu_titles" id="remenu_titles" value="" />

			<h2 style="margin-top:1.5em;"><?php esc_html_e( 'Sidebar layout', 'remenu' ); ?></h2>
			<ul id="remenu-tree" class="remenu-list remenu-root">
				<?php
				foreach ( $tree as $node ) :
					$slug = $node['slug'];
					$kind = isset( $node['kind'] ) ? $node['kind'] : ( isset( $sections[ $slug ] ) ? 'section' : 'item' );

					// Promoted submenu item sitting at the top level (a leaf node).
					if ( 'subitem' === $kind ) {
						if ( ! isset( $subitems[ $slug ] ) ) {
							continue;
						}
						remenu_render_subitem_chip( $slug, $subitems[ $slug ], $hidden, isset( $titles[ $slug ] ) ? $titles[ $slug ] : '' );
						continue;
					}

					$is_section = ( 'section' === $kind ) && isset( $sections[ $slug ] );
					if ( $is_section ) {
						$title = $sections[ $slug ]['title'];
						$icon  = ! empty( $sections[ $slug ]['icon_url'] ) ? $sections[ $slug ]['icon_url'] : $sections[ $slug ]['icon'];
						$sub   = '';
					} else {
						if ( ! isset( $orig[ $slug ] ) ) {
							continue;
						}
						$title = remenu_clean_title( $orig[ $slug ][0] );
						$icon  = isset( $orig[ $slug ][6] ) ? $orig[ $slug ][6] : '';
						$sub   = remenu_nonpromotable_sub_names( $slug, $orig_sub );
					}

					// Native promotable sub-pages that haven't been relocated yet.
					$native_chips = array();
					if ( ! $is_section && isset( $orig_sub[ $slug ] ) ) {
						foreach ( $orig_sub[ $slug ] as $s ) {
							$ss = isset( $s[2] ) ? $s[2] : '';
							if ( ! remenu_is_promotable_subitem( $ss, $slug ) || isset( $moved_sub[ $ss ] ) || ! isset( $subitems[ $ss ] ) ) {
								continue;
							}
							$native_chips[ $ss ] = $subitems[ $ss ];
						}
					}
					$has_children = ! empty( $node['children'] ) || ! empty( $native_chips );
					$node_hidden  = isset( $hidden[ $slug ] ) ? ' remenu-hidden' : '';
					?>
					<li class="remenu-node<?php echo $is_section ? ' remenu-section' : ''; ?><?php echo esc_attr( $node_hidden ); ?>" draggable="true" data-slug="<?php echo esc_attr( $slug ); ?>" data-type="<?php echo $is_section ? 'section' : 'item'; ?>" data-kind="<?php echo $is_section ? 'section' : 'item'; ?>">
						<div class="remenu-row">
							<span class="remenu-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to move', 'remenu' ); ?>"></span>
							<?php echo remenu_icon_markup( $icon, $is_section ); // phpcs:ignore ?>
							<?php if ( $is_section ) : ?>
								<span class="remenu-label"><?php echo esc_html( $title ); ?></span>
							<?php else : ?>
								<?php echo remenu_label_cell( $title, isset( $titles[ $slug ] ) ? $titles[ $slug ] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
							<?php if ( $is_section ) : ?>
								<span class="remenu-tag"><?php esc_html_e( 'section', 'remenu' ); ?></span>
							<?php endif; ?>
							<?php if ( $sub ) : ?>
								<span class="remenu-sub"><?php echo esc_html( $sub ); ?></span>
							<?php endif; ?>
							<?php echo remenu_hide_label( $slug, $hidden ); // phpcs:ignore ?>
						</div>
						<ul class="remenu-list remenu-children">
							<?php
							foreach ( $node['children'] as $child ) :
								$cslug = $child['slug'];
								$ckind = isset( $child['kind'] ) ? $child['kind'] : 'item';
								if ( 'subitem' === $ckind ) {
									if ( isset( $subitems[ $cslug ] ) ) {
										remenu_render_subitem_chip( $cslug, $subitems[ $cslug ], $hidden, isset( $titles[ $cslug ] ) ? $titles[ $cslug ] : '' );
									}
									continue;
								}
								if ( ! isset( $orig[ $cslug ] ) ) {
									continue;
								}
								remenu_render_item_child( $cslug, $orig, $orig_sub, ! empty( $child['flatten'] ), $hidden, isset( $titles[ $cslug ] ) ? $titles[ $cslug ] : '' );
							endforeach;

							foreach ( $native_chips as $ss => $info ) {
								remenu_render_subitem_chip( $ss, $info, $hidden, isset( $titles[ $ss ] ) ? $titles[ $ss ] : '' );
							}
							?>
							<?php if ( ! $has_children ) : ?>
								<li class="remenu-empty-hint"><?php esc_html_e( 'Drag items here to nest them', 'remenu' ); ?></li>
							<?php endif; ?>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>

			<h2 style="margin-top:2.25em;"><?php esc_html_e( 'Custom sections', 'remenu' ); ?></h2>
			<p class="description" style="max-width:820px;">
				<?php esc_html_e( 'Create a new top-level section, optionally with a Dashicons class or an uploaded icon image. After saving, it appears at the bottom of the layout above, ready to drag into place and drop items into.', 'remenu' ); ?>
			</p>
			<table class="widefat striped remenu-sections" style="max-width:980px;" id="remenu-sections">
				<thead>
					<tr>
						<th style="width:34%;"><?php esc_html_e( 'Section title', 'remenu' ); ?></th>
						<th style="width:26%;"><?php esc_html_e( 'Dashicon class', 'remenu' ); ?></th>
						<th><?php esc_html_e( 'Icon image', 'remenu' ); ?></th>
						<th style="width:70px;text-align:center;"><?php esc_html_e( 'Delete', 'remenu' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$si = 0;
				foreach ( $sections as $sslug => $meta ) :
					?>
					<tr>
						<td>
							<input type="hidden" name="remenu_section_slug[<?php echo (int) $si; ?>]" value="<?php echo esc_attr( $sslug ); ?>" />
							<input type="text" name="remenu_section_title[<?php echo (int) $si; ?>]" value="<?php echo esc_attr( $meta['title'] ); ?>" style="width:100%;" />
						</td>
						<td>
							<input type="text" name="remenu_section_icon[<?php echo (int) $si; ?>]" value="<?php echo esc_attr( $meta['icon'] ); ?>" placeholder="dashicons-menu-alt" style="width:100%;" />
						</td>
						<td class="remenu-icon-cell">
							<img class="remenu-icon-preview" src="<?php echo esc_url( $meta['icon_url'] ); ?>" alt="" style="<?php echo $meta['icon_url'] ? '' : 'display:none;'; ?>" />
							<input type="hidden" class="remenu-icon-url" name="remenu_section_icon_url[<?php echo (int) $si; ?>]" value="<?php echo esc_url( $meta['icon_url'] ); ?>" />
							<button type="button" class="button remenu-upload-icon"><?php esc_html_e( 'Upload', 'remenu' ); ?></button>
							<button type="button" class="button-link remenu-clear-icon" style="<?php echo $meta['icon_url'] ? '' : 'display:none;'; ?>"><?php esc_html_e( 'remove', 'remenu' ); ?></button>
						</td>
						<td style="text-align:center;">
							<input type="checkbox" name="remenu_section_delete[<?php echo (int) $si; ?>]" value="1" />
						</td>
					</tr>
					<?php
					$si++;
				endforeach;
				?>
					<tr>
						<td><input type="text" name="remenu_section_title[<?php echo (int) $si; ?>]" value="" placeholder="<?php esc_attr_e( 'New section name…', 'remenu' ); ?>" style="width:100%;" /></td>
						<td><input type="text" name="remenu_section_icon[<?php echo (int) $si; ?>]" value="" placeholder="dashicons-menu-alt" style="width:100%;" /></td>
						<td class="remenu-icon-cell">
							<img class="remenu-icon-preview" src="" alt="" style="display:none;" />
							<input type="hidden" class="remenu-icon-url" name="remenu_section_icon_url[<?php echo (int) $si; ?>]" value="" />
							<button type="button" class="button remenu-upload-icon"><?php esc_html_e( 'Upload', 'remenu' ); ?></button>
							<button type="button" class="button-link remenu-clear-icon" style="display:none;"><?php esc_html_e( 'remove', 'remenu' ); ?></button>
						</td>
						<td></td>
					</tr>
				</tbody>
			</table>
			<p><button type="button" class="button" id="remenu-add-section"><?php esc_html_e( 'Add another section', 'remenu' ); ?></button></p>

			<h2 style="margin-top:2.25em;"><?php esc_html_e( 'Submenu behavior', 'remenu' ); ?></h2>
			<p>
				<label>
					<input type="checkbox" name="remenu_accordion" value="1" <?php checked( ! empty( $layout['accordion'] ) ); ?> />
					<?php esc_html_e( 'Collapse submenus into click-to-open accordions (with an arrow toggle). The section for the page you are on opens automatically.', 'remenu' ); ?>
				</label>
			</p>

			<p style="margin-top:1.5em;">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save layout', 'remenu' ); ?></button>
			</p>
		</form>

		<form method="post" action="" onsubmit="return confirm('<?php echo esc_js( __( 'Reset ReMenu to the default sidebar layout?', 'remenu' ) ); ?>');">
			<?php wp_nonce_field( 'remenu_save_layout' ); ?>
			<input type="hidden" name="remenu_action" value="reset" />
			<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to default', 'remenu' ); ?></button>
		</form>
	</div>
	<?php
}