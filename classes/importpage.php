<?php
defined('ABSPATH') or die('No script kiddies please!');

/**
 *  import page .
 */
class SNAF_Import
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'importpage'));
    }

    public function importpage()
    {
        add_menu_page(
            __('Snillrik import page', 'snillrik-wpjb-import'),
            __('SNIM import', 'snillrik-wpjb-import'),
            'administrator',
            __FILE__,
            array($this, 'snillrik_wpjb_import'),
            plugins_url('../assets/snillrik_bulb.svg', __FILE__)
        );
    }

    public function snillrik_wpjb_import()
    {

        $categories_options_str = SNAF_WPJBDB::getSelectedOptionTag("category");
        $returnstr = SNAF_API::get_jobtypes_alt();
        $returnstr .= "
        <div class='wrap snillrik-wpjb-import'>
            <input type='hidden' id='snimp_joblist_offset' value=0 />
            <input type='hidden' id='snimp_joblist_selected' value='' />
            <button id='snimp-showallbutton' class='snimp-prettybutton'>" . __("Show all categories", 'snillrik-wpjb-import') . "</button>
            <button id='snimp_emptyselected' class='snimp-prettybutton'>" . __("Deselect all", 'snillrik-wpjb-import') . "</button>
            <button id='snimp-favouritesbutton' class='snimp-prettybutton'>" . __("Save as favorites", 'snillrik-wpjb-import') . "</button>
            <div class='snimp-searchbox'><h3>" . __("Fetch jobs from selected categories", 'snillrik-wpjb-import') . "</h3>
            
            <div id='snimp-selected-categories'>" . __("No categories selected", 'snillrik-wpjb-import') . "</div><br />
            <input type='text' id='snillrik-wpjb-q' placeholder='" . __("Text search", 'snillrik-wpjb-import') . "' />
            <button id='snimp-fetchbutton' class='snimp-prettybutton'>" . __("Get jobs", 'snillrik-wpjb-import', 'snillrik-wpjb-import') . "</button>
            <br /><small>" . __("Use * to search for parts of word*", 'snillrik-wpjb-import') . "</small>
            </div>
            <div class='snimp-loader'></div>
            <br /><div id='snimp_joblist'>
            </div>
            <div class='snimp-searchbox'>
                <h3>" . __("Change categories", 'snillrik-wpjb-import') . "</h3>
                <p>" . __("Change all selected jobs categories to the one selected here.", 'snillrik-wpjb-import') . "</p>
                <select id='snaf_change_cats' multiple>$categories_options_str</select>
                <button id='snimp-changeselected' class='snimp-prettybutton'>" . __("Change selected to", 'snillrik-wpjb-import') . "</button>
            </div>
            <div class='snimp-searchbox'>
                <h3>" . __("Save to Job Board", 'snillrik-wpjb-import') . "</h3>
                <p>" . __("Finally you are ready to save the jobs you have selected in the list.", 'snillrik-wpjb-import') . "</p>
            <button id='snimp-saveselected' class='snimp-prettybutton'>" . __("Save selected", 'snillrik-wpjb-import') . "</button>            
            <br /><div id='snimp_saverespons'>
            </div></div><br />
        </div>";


        echo $returnstr;
    }
}
