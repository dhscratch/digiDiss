<?php
/**
 * The template for digidiss items.
 */

get_header();
?>

<div id="content">
	<div class="quest-row" id="title-container">
		<div class="<?php echo apply_filters( 'quest_content_container_cls', 'container' ); ?> title-container">
			<div class="row">
				<div class="col-md-6">
					<h3><?php get_item_title(); ?></h3>
				</div>
				<div class="col-md-6">
					<ul class="breadcrumbs">
						<?php get_item_breadcrumbs(); ?>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="quest-row site-content">
		<div class="<?php echo apply_filters( 'quest_content_container_cls', 'container' ); ?>">
			<div class="row">
        <noscript>
        <div class="noscript_warning">PLEASE NOTE: JAVASCRIPT IS DISABLED ON YOUR BROWSER. For the best user experience, please enable javascript on your browser now.</div>
        </noscript>

				<div id="primary" class="content-area single col-md-12">
					<main id="main" class="site-main" role="main">
            <div id="digi-content" class="row">
              <div id="digi-loading"></div>
              <div id="digi-item-left" class="col-sm-6 one_half">
								<?php get_item_image(); ?>
								<?php get_related_content(); ?>
								<?php get_associated_files(); ?>
            	</div>
              <div id="digi-item-right" class="col-sm-6 last">
            		<div id="digi-item-details"><?php echo get_item_details($data); ?></div>
								<?php get_download_links(); ?>
            	</div>
							<div class="col-sm-12 digi-item-custom">
								<?php get_item_extension(); ?>
							</div>
            </div><!-- #digi-content -->

					</main>
					<!-- #main -->
				</div>
				<!-- #primary -->
			</div>
			<!-- .row -->
		</div>
		<!-- .container -->
	</div>
	<!-- .quest-row -->
</div><!-- #content -->
<?php get_footer(); ?>
<script type="text/javascript">add_google_tracking();</script>
