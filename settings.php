<?php
class SNAF_Settings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'settingspage'));
        add_action('wp_ajax_snaf_delete_to_date', array("SNAF_WPJBDB", "delete_expired_jobs"));
    }

    public function settingspage()
    {
        add_menu_page(
            __('Snillrik wpjb import Settings', 'snillrik-wpjb-import'),
            __('SNIM Settings', 'snillrik-wpjb-import'),
            'administrator',
            __FILE__,
            array($this, 'snillrik_wpjb_import_settings_page'),
            plugins_url('/assets/snillrik_bulb.svg', __FILE__)
        );
        $args = array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => null,
        );

        //add_action('admin_menu', array($this, 'import_settings'));
        register_setting('snillrik-wpjb-af-import-settings-group', 'snillrik_wpjb_token', $args);
        register_setting('snillrik-wpjb-af-import-settings-group', 'snillrik_wpjb_default_email', $args);
    }
    public function import_settings()
    {
        //register our settings
        //register_setting( 'snillrik-wpjb-af-import-settings-group', 'snillrik_wpjb_import_ids');
        // register_setting('snillrik-wpjb-af-import-settings-group', 'snillrik_wpjb_token');
    }

    public function snillrik_wpjb_import_settings_page()
    {
        $snillrik_af_token = get_option('snillrik_wpjb_token');
        $snillrik_default_email = get_option('snillrik_wpjb_default_email');
        $meta_arr = SNAF_WPJBDB::getMetasByType("job");
        $metas_chart = "";
        foreach ($meta_arr as $meta) {
            $values = unserialize($meta->meta_value);
            if (!$values["is_trashed"] && $values["title"] != "") {
                $metas_chart .= "<div class=''><strong>" . $values["title"] . "</strong>: " . $values["name"] . "</div>";
            }
        }
        echo "<div class='wrap snillrik-wpjb-af-import'>
	<h2>Snillrik import to WPJB</h2>
    <p>" . __('Below are some settings for connecting to Arbersförmedlingens API at <a href="https://jobtechdev.se/docs/jobsearch/">https://jobtechdev.se/docs/jobsearch/</a>', 'snillrik-wpjb-import') . "</p>";
        //$metas_chart = "";
/*         if ($metas_chart != "") {
            echo "<div><h3>Match theese with the stuff from AF. TODO</h3>" . $metas_chart . "</div>";
        } */
        echo "<div class='wpaf_leftside'>
	<form method='post' action='options.php'>";
        echo settings_fields('snillrik-wpjb-af-import-settings-group');
        echo do_settings_sections('snillrik-wpjb-af-import-settings-group');
        echo "    <table class='form-table'><tr><td>
		<h3>" . __('Token for Arbetsförmedlingen API', 'snillrik-wpjb-import') . "</h3>
		<input id='snillrik_wpjb_token' name='snillrik_wpjb_token' value='$snillrik_af_token'>
		</td></tr>
		<tr><td>
		<h3>Default email</h3>
		<p>" . __('Email that is used for contact if no email is set in fetched info.', 'snillrik-wpjb-import') . "</p>
		<input id='snillrik_wpjb_default_email' name='snillrik_wpjb_default_email' value='$snillrik_default_email'>
		</td></tr>
		</table>";
        submit_button();
        echo "</form>";

        echo "<h3>Filter</h3>";
        echo "For adding custom meta info from AF<br />";
        echo nl2br('<code>add_action("snjb_import_metas_before_insert", "my_metas_hands_off", 10, 2);

function my_metas_hands_off($metas, $annons)
{
	$metas = array(
		"annons_id" => $annons->id,
		"tjanster" => $annons->number_of_vacancies
	);
	return $metas;
}</code>');

        echo "<h3>Test</h3>";
        echo "<input type='date' id='delete_expired_jobs_until' placeholder='Until date' />";
        echo "<input type='button' id='delete_expired_jobs' value='Delete until date set' />";
        echo "<div id='delete_info'>
            <h4>Delete info</h4>
        </div>";
/*         $expired_jobs = SNAF_WPJBDB::delete_expired_jobs();
        print_r($expired_jobs); */

    }
}
