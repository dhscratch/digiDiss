<?php
/**
 * template for search/browse/collections/collection
 */

get_header();
$view = 'search';
?>

<div id="content">
	<?php quest_title_bar( $view ); ?>

	<div class="quest-row site-content">
		<div class="<?php echo apply_filters( 'quest_content_container_cls', 'container' ); ?>">
			<div id="digi-loading"></div>
			<div id="digi-selection" class="row" style="display:none"><div class="col-md-2"><h5>You've selected: </h5></div><div class="col-md-10"></div></div>
			<div id="digi-browse-header-row" class="row">
				<div id="digi-item-count" class="one_fourth col-xs-6 col-sm-4 col-md-2"></div>
				<div id="digi-per-page-div" class="one_fourth col-xs-6 col-sm-4 col-md-2"></div>
				<div id="digi-sort" class="one_fourth col-sm-4 col-md-3" style="display:none"></div>
				<div id="digi-pagination" class="one_fourth last col-sm-12 col-md-5"><ul class="pag pagination"></ul></div>
			</div><!-- #digi-browse-header-row -->
			<div class="row">

				<div id="primary" class="content-area col-md-9">
					<main id="main" class="site-main" role="main">

						<div id="digi-content" class="container">
							<div class="row">
								<div id="digi-facets" class="one_fourth col-md-3 hidden-phone hidden-xs hidden-sm"></div>
						  	<div id="digi-docs" class="three_fourth col-md-9 last">
							</div>
						  </div>
						</div><!-- #digi-content -->

					</main>
					<!-- #main -->
				</div>
				<!-- #primary -->

				<div id="secondary" class="widget-area main-sidebar col-md-3" role="complementary">

				</div><!-- #secondary -->

			</div>
			<!-- .row -->
		</div>
		<!-- .container -->
	</div>
	<!-- .quest-row -->
</div><!-- #content -->
<?php get_footer(); ?>
