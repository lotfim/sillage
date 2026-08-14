<?php
/**
 * Analytics dashboard admin view.
 *
 * @package    Sillage
 * @subpackage Sillage/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sillage_show_export = false;
?>
<div class="wrap sillage-wrap" id="sillage-analytics">
	<h1><?php echo esc_html__( 'Analytics', 'sillage' ); ?></h1>
	<p class="description">
		<?php echo esc_html__( 'Aggregated front-office visits by logged-in users. Average duration only includes visits with an exit time, which is best-effort and may be missing.', 'sillage' ); ?>
	</p>

	<?php require SILLAGE_PLUGIN_DIR . 'admin/views/partials/filters.php'; ?>

	<div class="sillage-presets sil-flex sil-flex-wrap sil-gap-2 sil-mb-5" role="group" aria-label="<?php echo esc_attr__( 'Quick date ranges', 'sillage' ); ?>">
		<button type="button" class="button sillage-preset" data-days="7"><?php echo esc_html__( 'Last 7 days', 'sillage' ); ?></button>
		<button type="button" class="button sillage-preset" data-days="30"><?php echo esc_html__( 'Last 30 days', 'sillage' ); ?></button>
		<button type="button" class="button sillage-preset" data-days="90"><?php echo esc_html__( 'Last 90 days', 'sillage' ); ?></button>
	</div>

	<div id="sillage-analytics-loading" class="sillage-dash-status" hidden>
		<?php echo esc_html__( 'Loading…', 'sillage' ); ?>
	</div>

	<div id="sillage-analytics-empty" class="sillage-dash-empty" hidden>
		<p class="sillage-dash-empty__title"><?php echo esc_html__( 'No visits in this period.', 'sillage' ); ?></p>
		<p class="description"><?php echo esc_html__( 'No visits recorded yet. Open a published page or post while logged in on the front of the site.', 'sillage' ); ?></p>
	</div>

	<div id="sillage-analytics-error" class="sillage-dash-empty" hidden>
		<p class="sillage-dash-empty__title"><?php echo esc_html__( 'The results could not be loaded.', 'sillage' ); ?></p>
	</div>

	<div id="sillage-analytics-body" hidden>
		<div class="sillage-kpis">
			<article class="sillage-kpi">
				<p class="sillage-kpi__label"><?php echo esc_html__( 'Visits', 'sillage' ); ?></p>
				<p class="sillage-kpi__value" data-kpi="visits">—</p>
			</article>
			<article class="sillage-kpi">
				<p class="sillage-kpi__label"><?php echo esc_html__( 'Unique users', 'sillage' ); ?></p>
				<p class="sillage-kpi__value" data-kpi="unique_users">—</p>
			</article>
			<article class="sillage-kpi">
				<p class="sillage-kpi__label"><?php echo esc_html__( 'Unique contents', 'sillage' ); ?></p>
				<p class="sillage-kpi__value" data-kpi="unique_contents">—</p>
			</article>
			<article class="sillage-kpi">
				<p class="sillage-kpi__label"><?php echo esc_html__( 'Average duration', 'sillage' ); ?></p>
				<p class="sillage-kpi__value sillage-kpi__value--duration" data-kpi="avg_duration">—</p>
			</article>
		</div>

		<section class="sillage-card sillage-card--wide">
			<header class="sillage-card__head">
				<h2 class="sillage-card__title"><?php echo esc_html__( 'Visits over time', 'sillage' ); ?></h2>
				<p class="sillage-card__meta" data-series-meta></p>
			</header>
			<div class="sillage-chart-wrap">
				<canvas id="sillage-chart-series" height="120"></canvas>
			</div>
		</section>

		<div class="sillage-dash-grid">
			<section class="sillage-card">
				<header class="sillage-card__head">
					<h2 class="sillage-card__title"><?php echo esc_html__( 'Top content', 'sillage' ); ?></h2>
				</header>
				<ol class="sillage-rank" id="sillage-top-contents"></ol>
			</section>
			<section class="sillage-card">
				<header class="sillage-card__head">
					<h2 class="sillage-card__title"><?php echo esc_html__( 'Top users', 'sillage' ); ?></h2>
				</header>
				<ol class="sillage-rank" id="sillage-top-users"></ol>
			</section>
			<section class="sillage-card">
				<header class="sillage-card__head">
					<h2 class="sillage-card__title"><?php echo esc_html__( 'By content type', 'sillage' ); ?></h2>
				</header>
				<div class="sillage-chart-wrap sillage-chart-wrap--donut">
					<canvas id="sillage-chart-types" height="220"></canvas>
				</div>
			</section>
		</div>
	</div>
</div>
