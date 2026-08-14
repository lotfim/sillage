<?php
/**
 * One-off demo seeder for WP.org screenshots (English content).
 *
 * Usage (Docker):
 *   wp eval-file wp-content/plugins/sillage/tools/seed-demo.php --allow-root
 *
 * Idempotent via option sillage_demo_seeded. Re-run with SILLAGE_SEED_FORCE=1
 * to wipe demo log rows tagged by nicename prefix and insert again.
 *
 * @package Sillage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Run via WP-CLI: wp eval-file tools/seed-demo.php' );
}

if ( ! class_exists( 'Sillage_Database' ) ) {
	WP_CLI::error( 'Activate the Sillage plugin first.' );
}

$force = ( '1' === getenv( 'SILLAGE_SEED_FORCE' ) );

if ( get_option( 'sillage_demo_seeded' ) && ! $force ) {
	WP_CLI::success( 'Demo data already present (sillage_demo_seeded). Set SILLAGE_SEED_FORCE=1 to reseed logs.' );
	return;
}

update_option( 'WPLANG', '' );
update_user_meta( 1, 'locale', 'en_US' );
switch_to_locale( 'en_US' );

$users_spec = array(
	array(
		'login' => 'jane.editor',
		'email' => 'jane.editor@example.com',
		'name'  => 'Jane Editor',
		'role'  => 'editor',
		'pass'  => wp_generate_password( 20, true, true ),
	),
	array(
		'login' => 'marcus.author',
		'email' => 'marcus.author@example.com',
		'name'  => 'Marcus Author',
		'role'  => 'author',
		'pass'  => wp_generate_password( 20, true, true ),
	),
	array(
		'login' => 'priya.member',
		'email' => 'priya.member@example.com',
		'name'  => 'Priya Member',
		'role'  => 'subscriber',
		'pass'  => wp_generate_password( 20, true, true ),
	),
	array(
		'login' => 'alex.reviewer',
		'email' => 'alex.reviewer@example.com',
		'name'  => 'Alex Reviewer',
		'role'  => 'author',
		'pass'  => wp_generate_password( 20, true, true ),
	),
);

$user_ids = array();

foreach ( $users_spec as $spec ) {
	$existing = get_user_by( 'login', $spec['login'] );
	if ( $existing ) {
		$user_ids[] = (int) $existing->ID;
		continue;
	}
	$id = wp_insert_user(
		array(
			'user_login'   => $spec['login'],
			'user_pass'    => $spec['pass'],
			'user_email'   => $spec['email'],
			'display_name' => $spec['name'],
			'nickname'     => $spec['name'],
			'role'         => $spec['role'],
			'locale'       => 'en_US',
		)
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		continue;
	}
	$user_ids[] = (int) $id;
	WP_CLI::log( 'Created user ' . $spec['login'] );
}

$pages = array(
	array(
		'title'   => 'About the studio',
		'content' => 'We design quiet, useful tools for editorial teams. This page is demo content for Sillage screenshots.',
		'slug'    => 'about-the-studio',
	),
	array(
		'title'   => 'Services',
		'content' => 'Workshops, retainers, and product design. Demo page used to generate visit volume.',
		'slug'    => 'services',
	),
	array(
		'title'   => 'Contact',
		'content' => 'Reach the studio on weekdays. Demo page.',
		'slug'    => 'contact',
	),
	array(
		'title'   => 'Privacy policy',
		'content' => 'This demo site discloses visit logging for logged-in users via the Sillage plugin.',
		'slug'    => 'privacy-policy',
	),
);

$page_ids = array();

foreach ( $pages as $page ) {
	$found = get_page_by_path( $page['slug'] );
	if ( $found instanceof WP_Post ) {
		$page_ids[] = (int) $found->ID;
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'post_content' => $page['content'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		continue;
	}
	$page_ids[] = (int) $id;
	WP_CLI::log( 'Created page ' . $page['slug'] );
}

$posts = array(
	array( 'title' => 'How we run design critiques', 'slug' => 'design-critiques' ),
	array( 'title' => 'A short guide to editorial calendars', 'slug' => 'editorial-calendars' ),
	array( 'title' => 'Shipping the documentation last', 'slug' => 'shipping-documentation' ),
	array( 'title' => 'Notes from a quiet Monday', 'slug' => 'quiet-monday' ),
	array( 'title' => 'Why we still write weekly recaps', 'slug' => 'weekly-recaps' ),
);

$post_ids = array();

foreach ( $posts as $post ) {
	$found = get_page_by_path( $post['slug'], OBJECT, 'post' );
	if ( $found instanceof WP_Post ) {
		$post_ids[] = (int) $found->ID;
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => $post['title'],
			'post_name'    => $post['slug'],
			'post_content' => 'Demo article used to populate the Sillage visit log and analytics dashboard.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => 1,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		continue;
	}
	$post_ids[] = (int) $id;
	WP_CLI::log( 'Created post ' . $post['slug'] );
}

$objects = array();
foreach ( $page_ids as $id ) {
	$objects[] = array( 'id' => $id, 'weight' => 4, 'type' => 'page' );
}
foreach ( $post_ids as $id ) {
	$objects[] = array( 'id' => $id, 'weight' => 2, 'type' => 'post' );
}

if ( array() === $objects || array() === $user_ids ) {
	WP_CLI::error( 'Need at least one user and one piece of content.' );
}

global $wpdb;
$table = Sillage_Database::table();

if ( $force ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DELETE FROM {$table} WHERE user_email LIKE '%@example.com' OR user_nicename IN ('lotfim','janeeditor','marcusauthor','priyamember','alexreviewer')" );
}

$ips = array(
	'203.0.113.10',
	'203.0.113.24',
	'198.51.100.8',
	'198.51.100.44',
	'192.0.2.17',
	'192.0.2.81',
	'2001:db8:85a3::8a2e:370:7334',
);

$tz      = wp_timezone();
$now     = new DateTimeImmutable( 'now', $tz );
$weights = array();
foreach ( $objects as $i => $obj ) {
	for ( $w = 0; $w < $obj['weight']; $w++ ) {
		$weights[] = $i;
	}
}

$rows = 0;
$target = 140;

for ( $n = 0; $n < $target; $n++ ) {
	$user = get_userdata( $user_ids[ array_rand( $user_ids ) ] );
	if ( ! $user ) {
		continue;
	}
	$obj   = $objects[ $weights[ array_rand( $weights ) ] ];
	$post  = get_post( $obj['id'] );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	$days_ago  = random_int( 0, 27 );
	$hour      = random_int( 8, 18 );
	$minute    = random_int( 0, 59 );
	$entry     = $now->modify( "-{$days_ago} days" )->setTime( $hour, $minute, random_int( 0, 59 ) );
	$has_exit  = ( random_int( 1, 10 ) > 2 );
	$exit      = $has_exit ? $entry->modify( '+' . random_int( 45, 900 ) . ' seconds' ) : null;
	$token     = hash( 'sha256', 'sillage-demo-' . $n . '-' . microtime( true ) . '-' . wp_generate_password( 12, false ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$ok = $wpdb->insert(
		$table,
		array(
			'user_id'       => (int) $user->ID,
			'user_nicename' => substr( (string) $user->user_nicename, 0, 60 ),
			'user_email'    => substr( (string) $user->user_email, 0, 100 ),
			'ip_address'    => $ips[ array_rand( $ips ) ],
			'object_id'     => (int) $post->ID,
			'object_title'  => substr( wp_strip_all_tags( get_the_title( $post ) ), 0, 255 ),
			'object_type'   => substr( (string) $post->post_type, 0, 40 ),
			'entry_date'    => $entry->format( 'Y-m-d H:i:s' ),
			'exit_date'     => $exit ? $exit->format( 'Y-m-d H:i:s' ) : null,
			'session_token' => $token,
		),
		array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( $ok ) {
		++$rows;
	}
}

update_option( 'sillage_demo_seeded', '1' );

WP_CLI::success( sprintf( 'Seeded %d visit rows, %d users, %d pages, %d posts. Admin locale set to en_US.', $rows, count( $user_ids ), count( $page_ids ), count( $post_ids ) ) );
