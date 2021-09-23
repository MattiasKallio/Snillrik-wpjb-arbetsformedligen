<?php

if (!defined('ABSPATH')) {
    exit;
}
/**
 * Provides functionality to communicate with AF's Web API.
 */
require_once SNIMP_DIR . 'classes/wpjb-db.php';

class SNAF_API
{
    public function __construct()
    {
        add_action('wp_ajax_snaf_get_occupations', array($this, "get_occupations"));
        add_action('wp_ajax_snaf_save_favourites', array($this, "save_favourites"));
        add_action('wp_ajax_snaf_save_to_wpjb', array($this, "save_to_wpjb"));
    }

    public function save_to_wpjb()
    {
        $selected_jobs = isset($_POST["selected"]) ? $_POST["selected"] : array();
        $selected_cats = isset($_POST["selected_cats"]) ? $_POST["selected_cats"] : array();
        $occupations = isset($_POST["occupations"]) ? (array) $_POST["occupations"] : array();
        $search_string = isset($_POST["q"]) ? sanitize_text_field($_POST["q"]) : "";
        $offset = isset($_POST["offset"]) ? sanitize_text_field($_POST["offset"]) : 0;

        $option_str = 'snaf_jobs' . implode("-", $occupations) . $search_string . $offset;
        $body_array = get_transient('snaf_jobs' . implode("-", $occupations) . $search_string . $offset);
        echo "<h4>" . __("Jobs saved", 'snillrik-wpjb-import') . "</h4>";

        if ($body_array) {
            foreach ($body_array->hits as $job) {
                if (in_array($job->id, $selected_jobs)) {
                    $selected_cat = isset($selected_cats[$job->id]) ? $selected_cats[$job->id] : "";
                    $respons = SNAF_WPJBDB::save2WBJB($job, $selected_cat);
                    if (!$respons["error"]) {
                        $headline = $job->headline;
                        echo $headline . "<br />\n";
                    } else {
                        echo "Error: " . print_r($respons["error"], true);
                    }
                }
            }
        }

        wp_die();
    }

    /**
     * To fetch all jobs from a specific occupation
     */
    public function get_occupations()
    {
        $occupations = isset($_POST["occupations"]) ? (array) $_POST["occupations"] : array();
        $search_string = isset($_POST["q"]) ? sanitize_text_field($_POST["q"]) : "";
        $offset = isset($_POST["offset"]) ? sanitize_text_field($_POST["offset"]) : 0;

        $alreadyfetched = get_transient('snaf_jobs' . implode("-", $occupations) . $search_string . $offset);
        $counter_jobs = 0;
        if (!$alreadyfetched) {
            $APItoken = get_option("snillrik_wpjb_token");
            $occustr = count($occupations) > 0 ? "&occupation-group=" . implode("&occupation-group=", $occupations) : "";
            $search_string_urled = urlencode($search_string);
            $urlen = "https://jobsearch.api.jobtechdev.se/search?q=$search_string_urled" . $occustr . "&offset=$offset&limit=50&sort=pubdate-desc";
            $response = wp_remote_post(
                $urlen,
                array(
                    'method' => 'GET',
                    'headers' => array('api-key' => $APItoken),
                )
            );
            $body_array = isset($response["body"]) ? json_decode($response["body"]) : false;

            if (count($body_array->errors) == 0) {
                set_transient('snaf_jobs' . implode("-", $occupations) . $search_string . $offset, $body_array, 86400);
            } else {
                echo wp_send_json(
                    array("html_out" => print_r($body_array->errors, true))
                );

                wp_die();
            }
        } else {
            $body_array = $alreadyfetched;
        }

        $strout = "";

        $total = isset($body_array->total->value) && $body_array->total->value > 0
            ? $body_array->total->value
            : "<h4>Inga jobba hittade</h4>";
        $added_job_array = get_transient("snaf_importedjobs");
        $prev_imported = 0;
        $strout .= "
        <tr>
            <th></th>
            <th>" . __('Rubrik', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Område (Yrke)', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Kategori, yrke', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Publicerad', 'snillrik-wpjb-import') . "</th>
            <th>" . __('AF Webb', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Kommun', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Arbetstid', 'snillrik-wpjb-import') . "</th>
            <th>" . __('Lön', 'snillrik-wpjb-import') . "</th>

        </tr>
    ";
        foreach ($body_array->hits as $job) {

            $jobid = $job->id;
            $headline = $job->headline;
            $publication_date = $job->publication_date;
            $url = $job->webpage_url; // AF
            $kommun = $job->workplace_address->municipality;
            $employment_type = $job->employment_type->label;
            $salary_type = $job->salary_type->label;
            $working_hours_type = $job->working_hours_type->label;
            $application_details = ""; //print_r($job->employer,true);

            $field = $job->occupation_field->label;
            $occugroup = $job->occupation_group->label;
            $occuspec = $job->occupation->label;

            $categories_options_str = SNAF_WPJBDB::getSelectedOptionTag("category", $occuspec);
            $alredy_imported = in_array($jobid, $added_job_array);
            if ($alredy_imported) {
                $prev_imported++;
            }

            $counter_jobs++;

            $disabled_imported = $alredy_imported ? "disabled" : "";
            $disabled_color = $alredy_imported ? "class='snaf_disabled_color'" : "";
            $strout .= "<tr $disabled_color><td><input type='checkbox' id='$jobid' $disabled_imported /></td>
                <td><strong>" . $headline . "</strong></td>
                <td><b>Occupation:</b> $occuspec<br />
                <b>Occupation group:</b> ($occugroup)<br />
                <b>Occupation field:</b> ($field)<br />
                </td>";
            if (!$alredy_imported) {
                $strout .= "<td><select class='snaf_cat_selected' id='snaf_cat_selected_$jobid' multiple>$categories_options_str</select></td>";
            } else {
                $strout .= "<td>$field</td>";
            }

            $strout .= "<td>" . substr($publication_date, 0, 10) . "</td>
                <td><a href='$url' target='_blank'>Webb</a></td>
                <td>" . $kommun . "</td>
                <td><strong>" . $working_hours_type . "</strong> - " . $employment_type . "</td>
                <td>" . $salary_type . " $application_details</td>

            </tr>";
        }
        $pages_str = "";
        for ($pages = 0; $pages < $total && $pages < 2000; $pages += 50) {
            $pages_str .= "<button class='snimp-pagebutton snimp-prettybutton' id=$pages>" . ($pages / 50) . "</button>";
        }

        echo wp_send_json(
            array("html_out" => "<h2>" . __("Total", 'snillrik-wpjb-import') . ": $total</h2><h4>"
                . __("Previously imported", 'snillrik-wpjb-import')
                . ": $prev_imported</h4><table class='snimp_table'>"
                . $strout . "</table>
                <div class='snimp-paginator'>$pages_str</div>")
        );

        wp_die();
    }

    /**
     * TO save favourites from af.
     */
    public function save_favourites()
    {
        $occupations = isset($_POST["occupations"]) ? ($_POST["occupations"]) : false;
        update_option("snimp_af_favourites", $occupations);
    }

    /**
     * To fetch all occupations use the one below.
     */
    /*     public function get_jobtypes()
{
$APItoken = get_option("snillrik_wpjb_token");
$typelevel = "ssyk-level-4";
//$APItoken = get_option("snillrik_wpjb_token");
$body_array = get_option("snimp_taxonomy_" . $typelevel);

if (!$body_array) {
$urlen = "https://taxonomy.api.jobtechdev.se/v1/taxonomy/main/concepts?type=$typelevel";
$response = wp_remote_post(
$urlen,
array(
'method' => 'GET',
'headers' => array('api-key' => $APItoken),
)
);
$body_array = isset($response["body"]) ? json_decode($response["body"]) : false;
update_option("snimp_taxonomy_" . $typelevel, $body_array);
}
$rowconter = 0;
$temp_str = "";
$out_str = "";
$favourites = get_option("snimp_af_favourites", array());

//print_r($body_array);
foreach ($body_array as $type) {
//print_r($type);

$rowconter++;
if ($rowconter % (count($body_array) / 4) == 0) {

$out_str .= "<div class='snimp_col'>$temp_str</div>";
$temp_str = "";
}
$selected_str = in_array($type->{"taxonomy/id"}, $favourites) ? "snimp_selected" : "";
//$temp_str .= $type->{"taxonomy/id"}.": ".$type->{"taxonomy/preferred-label"}."<br />";
$temp_str .= "<div class='snimp_selectbox $selected_str' id='snimp-selectid-" . $type->{"taxonomy/id"} . "'>" . $type->{"taxonomy/preferred-label"} . "</div>";
}
return "<h1>" . __("All the categories", 'snillrik-wpjb-import') . "</h1>

<div class='snimp_row snimp_selectmain'>" . $out_str . "</div>";
} */

    /**
     * Get all the job types from ssyk-level-4 that is sorted alphabetically.
     */
    public static function get_jobtypes_alt()
    {
        $APItoken = get_option("snillrik_wpjb_token");
        $typelevel = "ssyk-level-4";
        $body_array = get_option("snimp_taxonomy_" . $typelevel);

        if (!$body_array) {
            $urlen = "https://taxonomy.api.jobtechdev.se/v1/taxonomy/main/concepts?type=$typelevel";
            $response = wp_remote_post(
                $urlen,
                array(
                    'method' => 'GET',
                    'headers' => array('api-key' => $APItoken),
                )
            );
            $body_array = isset($response["body"]) ? json_decode($response["body"]) : false;
            update_option("snimp_taxonomy_" . $typelevel, $body_array);
        }
        $rowconter = 0;
        $temp_str = "";
        $out_str = "";
        $favourites = get_option("snimp_af_favourites", array());

        $out_array = array();
        if (is_array($body_array)) {
            foreach ($body_array as $type) {
                $selected_str = is_array($favourites) && in_array($type->{"taxonomy/id"}, $favourites) ? "snimp_selected" : "";
                $temp_str = "<div class='snimp_selectbox $selected_str' id='snimp-selectid-" . $type->{"taxonomy/id"} . "'>" . $type->{"taxonomy/preferred-label"} . "</div>";
                $out_array[$type->{"taxonomy/preferred-label"}] = $temp_str;
            }
            $temp_str = "";
            ksort($out_array);
            foreach ($out_array as $type) {
                $rowconter++;
                if ($rowconter % (count($body_array) / 4) == 0) {
                    $out_str .= "<div class='snimp_col'>$temp_str</div>";
                    $temp_str = "";
                }
                $temp_str .= $type;
            }
            return "<h1>" . __("All the categories", 'snillrik-wpjb-import') . "</h1>
            <p>" . __("These are all the level 4 categories from Arbetsförmedlingen. Select the once you want and search within those categories. If you want to free search, simply select no categories.", 'snillrik-wpjb-import') . "</p>
            <div class='snimp_row snimp_selectmain'>" . $out_str . "</div>";
        } else {
            return __("Nooo something went terrebly wrong!");
        }
    }
}
