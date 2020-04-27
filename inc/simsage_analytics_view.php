<?php
/**
 * the "view" of the SimSage analytics page
 */
?>



<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <div id="icon-themes" class="icon32"></div>
    <h2>SimSage Analytics</h2>

	<?php
	$options = get_option( PLUGIN_NAME );
    // when we have selected a site, this variable will be set
    $has_sites = isset($options['simsage_site']);
    ?>

    <script lang="js">
        // set an image base for all our templates to use (url bases for images)
        image_base = "<?php echo $this->asset_folder ?>";
        server = "<?php echo $this->get_account_setting("server") ?>";

        // the settings for this application - no trailing / on the base_url please
        settings = {
            // api version of ws_base
            api_version: 1,
            // the service layer end-point, set after logging in
            base_url: server + 'api',
            // the organisation's id to search
            organisationId: "<?php echo $this->get_account_setting("id") ?>",
            // the knowledge base's Id (selected site) and security id (sid)
            kbId: "<?php echo $this->get_site_setting("kbId") ?>",
            // the operator uses the SecurityID to verify themselves, do not expose it to your web users!
            sid: "<?php echo $this->get_site_setting("sid") ?>",
        };

    </script>

    <div class="analytics-area">


    </div>

</div>
