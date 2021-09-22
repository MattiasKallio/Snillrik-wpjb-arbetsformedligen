<?php

if (!defined('ABSPATH')) {
    exit;
}
/**
 * Provides functionality to communicate with AF's Web API.
 */
class SNAF_WPJBDB
{
    public function __construct()
    {
        //add_shortcode("kallio_experimenterar",[$this,"get_expired_jobs"]);

    }

    public function save2WBJB($annons, $selected_cat)
    {
        
        $error_info = "";
        if (isset($annons->headline) && $annons->headline != "") {
            global $wpdb;

            $added_job_array = get_transient("snaf_importedjobs");
            if (in_array($annons->id, $added_job_array)) {
                return array(
                    "respons" => __("Added", 'snillrik-wpjb-import'),
                    "error" => __("Alredy added", 'snillrik-wpjb-import'),
                );
            }

            $return_info = "ok";
            $error_info = "";

            $error_info .= $annons->employer->url == "" ? __("No url is set for:") . $annons->headline . "\n" : "";

            // Insert into post
            $args_post = array(
                "post_title" => $annons->headline,
                "post_status" => "publish",
                "post_type" => "job",
            );

            $post_id = wp_insert_post($args_post);
            
            // Insert into job
            if (is_numeric($post_id) && $post_id != 0) {
                $wppost = get_post($post_id);
                $table_name = $wpdb->prefix . "wpjb_job";
                $snillrik_wpjb_default_email = get_option("snillrik_wpjb_default_email", "n/a");

                $insert_array = array(
                    'post_id' => $post_id,
                    'job_title' => $wppost->post_title,
                    'job_slug' => $wppost->post_name,
                    'job_description' => $annons->headline
                    . "\n" . $annons->description->text
                    . "\n" . $annons->workplace_address->region
                    . "\n" . $annons->workplace_address->municipality
                    . "\n" . $annons->employment_type->duration->label . " " . $annons->working_hours_type->duration->label
                    . "\n",
                    'job_created_at' => $annons->publication_date,
                    'job_expires_at' => $annons->application_deadline,
                    'job_zip_code' => $annons->workplace_address->municipality == "" ? "n/a" : $annons->workplace_address->municipality,
                    'job_city' => $annons->workplace_address->region,
                    'company_name' => $annons->employer->name,
                    'company_url' => $annons->employer->url == "" ? $annons->webpage_url : $annons->employer->url,
                    'company_email' => $annons->employer->email == "" ? $snillrik_wpjb_default_email : $annons->employer->email,
                    'is_approved' => 1,
                    'is_active' => 1,
                );

                $worked = $wpdb->insert($table_name, $insert_array);
                $error_info .= $wpdb->last_error != "" ? $wpdb->last_error . "\n" : "";

                $wpjob_id = $wpdb->insert_id;

                // insert into serch table
                $search_table_name = $wpdb->prefix . "wpjb_job_search";
                $search_insert = $wpdb->insert($search_table_name, array(
                    "job_id" => $wpjob_id,
                    "title" => $annons->headline,
                    "description" => $annons->headline
                    . "\n" . (isset($annons->description->text_formatted) && $annons->description->text_formatted != "" ? $annons->description->text_formatted : $annons->description->text)
                    . "\n" . $annons->workplace_address->region
                    . "\n" . $annons->workplace_address->municipality
                    . "\n" . $annons->employment_type->duration->label . " " . $annons->working_hours_type->duration->label . "\n", // ,
                    "company" => $annons->employer->name,
                    "location" => $annons->workplace_address->municipality,
                ));

                // Insert tags ie category and type
                // Flera categorier | .

                $the_cat = $selected_cat == "" ? $annons->occupation_group->label : $selected_cat;
                $categories_list = explode("|", $the_cat);
                foreach ($categories_list as $catname) {
                    $category = SNAF_WPJBDB::getTagByStr($catname);
                    if ($catname != "") {
                        SNAF_WPJBDB::insert_wpjb_tag(array(
                            'tag_id' => $category[0]->id,
                            'object' => "job",
                            'object_id' => $wpjob_id,
                        ));

                    } else {
                        __("No category set", 'snillrik-wpjb-import');
                    }
                }

                $types_list = explode("|", $annons->working_hours_type->label);
                foreach ($types_list as $typen) {

                    $type_arr = SNAF_WPJBDB::getTagByStr($typen);
                    $type = $type_arr[0];
                    SNAF_WPJBDB::insert_wpjb_tag(array(
                        'tag_id' => $type->id,
                        'object' => "job",
                        'object_id' => $wpjob_id,
                    ));
                }
                // Custom meta spara.
                /* If you want to add info from AF to custom meta fields.
                 
                $contact_str = "";
                $contact_str .= $annons->application_details->email == "" ? "" : $annons->application_details->email . "\n";
                //$contact_str .= $annons->application_details->url == "" ? "" : $annons->application_details->url;
                $expdate = strtotime($annons->application_deadline);
                $expdate_out = date('Y/m/d', $expdate);

                $beskrivning2 = "Yrke: " . $annons->occupation_group->label . "\n"
                . "Anställningstyp: " . $annons->employment_type->label . "\n"
                . "Lön: " . $annons->salary_type->label . " " . $annons->salary_description->label . "\n";


                    $metas = array(
                    "id_jobb_match" => $annons->id,
                    "ansokning_date" => $expdate_out,
                    "job_lan" => trim(str_replace(array("s län", " län"), array("", ""), $annons->workplace_address->region)),
                    "field_6" => $contact_str,
                    'field_19' => "", //$beskrivning2,
                    'beskrivning2' => $annons->description->text, //$annons->occupation_group->label . __(" at ") . $annons->employer->name,
                    //'beskrivning3' => $must_have . "\n" . $nice_to_have,
                    'tjanster' => $annons->number_of_vacancies,
                    "field_24" => "", //$annons->description->text,
                    "url_till_jobb" => $annons->application_details->url == "" ? $annons->webpage_url : $annons->application_details->url,
                );

                $metas["url_till_jobb"] = strpos($metas["url_till_jobb"], "http") === false ? "//" . $metas["url_till_jobb"] : $metas["url_till_jobb"]; */
                $metas = []; // no extra fields.

                
                //do_action("snjb_import_metas_before_insert",$metas, $annons);
                $metas = apply_filters("snjb_import_metas_before_insert",$metas, $annons);
                
                foreach ($metas as $key => $value) {
                    $meta_arr = SNAF_WPJBDB::getMetaByStr($key, "job");
                    $meta = $meta_arr[0];
                    SNAF_WPJBDB::insert_wpjb_meta(array(
                        'meta_id' => $meta->id,
                        'value' => $value,
                        'object_id' => $wpjob_id,
                    ));
                }
            }

            if ($added_job_array) {
                $added_job_array[] = $annons->id;
            } else {
                $added_job_array = array($annons->id);
            }

            set_transient('snaf_importedjobs', $added_job_array, 86400 * 180); //alltså typ ett halvår.
            $return_info = "ok";
        } else {
            $error_info .= __("No title: ", 'snillrik-wpjb-import') . nl2br(print_r($annons, true));
        }

        return array(
            "respons" => $return_info,
            "error" => ($error_info == "" ? false : $error_info),
        );
    }

    /**
     * Get tag by string
     *
     * @param string $tagtitle
     * @return object|NULL
     */
    public static function getTagByStr($tagtitle)
    {
        global $wpdb;

        $sql = "SELECT * FROM " . $wpdb->prefix . "wpjb_tag WHERE title = '$tagtitle' LIMIT 1";
        $tag = $wpdb->get_results($sql);

        return $tag;
    }
/**
 * Get tags by type
 *
 * @param string $tagtitle
 * @return object|NULL
 */
    public static function getTagsByType($tagtype, $to_options = true)
    {
        global $wpdb;
        $return_array = array();
        $return_str = "";
        $sql = "SELECT * FROM " . $wpdb->prefix . "wpjb_tag WHERE type = '$tagtype'";
        $tags = $wpdb->get_results($sql);

        if ($to_options) {
            foreach ($tags as $tag) {
                $return_str .= "<option id='tag_$tag->id'>$tag->title</option>\n";
            }
            return $return_str;
        }
        return $tags;
    }

    public static function getSelectedOptionTag($tagtype, $selcted="")
    {
        $current_tags = get_transient("snaf_currenttags");
        $return_str = "<option id=0>" . __("Change category", 'snillrik-wpjb-import') . "</option>";
        if (!$current_tags) {
            $current_tags = SNAF_WPJBDB::getTagsByType($tagtype, false);
            set_transient("snaf_currenttags", $current_tags, 30);
        }

        foreach ($current_tags as $tag) {
            $selected_str = $selcted == $tag->title ? "selected" : "";
            $return_str .= "<option id='tag_$tag->id' $selected_str>$tag->title</option>\n";
        }
        return $return_str;
    }

/**
 * Get all meta by and type
 *
 * @param string $metaname
 * @param string $type
 * @return object|NULL
 */
    public static function getMetasByType($type = "")
    {
        global $wpdb;

        $sql = "SELECT * FROM " . $wpdb->prefix . "wpjb_meta WHERE meta_object = '$type'";
        $meta = $wpdb->get_results($sql);

        return $meta;
    }

/**
 * Get meta by string and type
 *
 * @param string $metaname
 * @param string $type
 * @return object|NULL
 */
    public static function getMetaByStr($metaname, $type = "")
    {
        global $wpdb;

        $sql = "SELECT * FROM " . $wpdb->prefix . "wpjb_meta WHERE name = '$metaname' AND meta_object = '$type' LIMIT 1";
        $meta = $wpdb->get_results($sql);

        return $meta;
    }

/**
 * Save meta
 */
    public static function insert_wpjb_meta($args)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "wpjb_meta_value";

        $insert_array = array(
            'meta_id' => $args["meta_id"],
            'value' => $args["value"],
            'object_id' => $args["object_id"],
        ); // ie job id

        $worked = $wpdb->insert($table_name, $insert_array);

        if ($worked) {
            return true;
        } else {
            return "error: " . "pr:" . print_r($table_name, true) . "pr:" . print_r($insert_array, true) . $wpdb->print_error();
        }

    }

/**
 * Save tag
 */
    public static function insert_wpjb_tag($args)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "wpjb_tagged";

        $insert_array = array(
            'tag_id' => $args["tag_id"],
            'object' => $args["object"],
            'object_id' => $args["object_id"],
        ); // ie job id

        $worked = $wpdb->insert($table_name, $insert_array);

        if ($worked) {
            return true;
        } else {
            return "error: "
            . "table:" . print_r($table_name, true)
            . "inserts:" . print_r($insert_array, true)
            . $wpdb->print_error();
        }

    }

    public function delete_expired_jobs()
    {

        $to_date = isset($_POST["todate"]) ? sanitize_text_field($_POST["todate"]) : false;
        global $wpdb;

        if(!$to_date){
            echo wp_send_json(
                array("result" => 'error')
            );
            wp_die();
        }

        //$to_date = date('Y-m-d H:s:i', strtotime('-1 week'));
        $to_date = date('Y-m-d H:s:i', strtotime($to_date));
        $sql = "SELECT ID as id FROM " . $wpdb->prefix . "wpjb_job WHERE job_expires_at <= '$to_date' ORDER BY job_expires_at DESC LIMIT 100";
        $jobs = $wpdb->get_results($sql);
        $jobs_str = "";
        $jobids = array("first"=>0, "last"=>0);
        foreach ($jobs as $job) {
            $job_id = $job->id;
            if($jobids["first"]==0)
                $jobids["first"] = $job_id;
            $jobids["last"] = $job_id;
            $jbb_job = new Wpjb_Model_Job($job_id);
            $jobs_str .= $job_id . " deleted<br />";
            $jbb_job->delete();
        }
        //return $jobs_str;

        echo wp_send_json(
            array(
                "result" => count($jobs) == 0 ? 'done' : 'go',
                "info" =>$jobids["first"]."-".$jobids["last"]."(".count($jobs).")"
            )
        );

        wp_die();

    }

}
