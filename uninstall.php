<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Intentionally a no-op for stored data: the visit log and settings are
 * kept so history is not lost. The only deletion paths are the retention
 * cron and the WordPress personal-data eraser.
 *
 * @package Sillage
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
