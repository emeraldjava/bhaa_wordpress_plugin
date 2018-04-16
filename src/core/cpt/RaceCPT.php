<?php
/**
 * Created by IntelliJ IDEA.
 * User: e074820
 * Date: 15/03/2018
 * Time: 17:31
 */

namespace BHAA\core\cpt;

use BHAA\core\Mustache;
use BHAA\core\race\RaceResult;

use BHAA\utils\Loadable;
use BHAA\utils\Loader;

class RaceCPT implements Loadable {

    const BHAA_RACE_DISTANCE = 'bhaa_race_distance';
    const BHAA_RACE_UNIT = 'bhaa_race_unit';
    const BHAA_RACE_TYPE = 'bhaa_race_type';
    const BHAA_RACE_TEAM_RESULTS = 'bhaa_race_team_results';

    public function registerHooks(Loader $loader) {
        $loader->add_action('init',$this,'bhaa_register_race_cpt');
        $loader->add_action('add_meta_boxes',$this,'bhaa_race_meta_data');
        $loader->add_action('add_meta_boxes',$this,'bhaa_team_meta_data');
        $loader->add_action('save_post',$this,'bhaa_save_race_meta');
        $loader->add_action('admin_action_bhaa_race_delete_results',$this,'bhaa_race_delete_results');
        $loader->add_action('admin_action_bhaa_race_load_results',$this,'bhaa_race_load_results');
        $loader->add_action('admin_menu',$this,'bhaa_race_sub_menu');
        $loader->add_filter('single_template',$this,'bhaa_cpt_race_single_template');
        $loader->add_filter('post_row_actions',$this,'bhaa_race_post_row_actions',10,2);
    }

    function bhaa_cpt_race_single_template($template) {
        if ('race' == get_post_type(get_queried_object_id())) {
            global $post_id;
            // load results
            $raceResult = new RaceResult();
            $res = $raceResult->getRaceResults(get_the_ID());
            // call the template
            $mustache = new Mustache();
            $raceResultTable = $mustache->renderTemplate(
                'race.results.individual',
                array(
                    'runners'=>$res,
                    'isAdmin'=>false,
                    'formUrl'=>home_url(),
                    'racename'=>'racename',
                    'dist'=>'dist',
                    'unit'=>'unit',
                    'type'=>'type'));
            set_query_var( 'raceResultTable', $raceResultTable );
            $template = plugin_dir_path(__FILE__) . '/partials/race/race.php';
        }
        return $template;
    }

    /**
     * Add sub-menus under the BHAA Races to edit all race results or a specific one. Make first parameter null to hide.
     * 'edit.php?post_type=race'
     */
    function bhaa_race_sub_menu() {
        // see http://websitesthatdontsuck.com/2011/11/adding-a-sub-menu-to-a-custom-post-type/ - 'edit.php?post_type=race'
        add_submenu_page( null, 'Edit Results', 'Edit Results',
            'manage_options', 'bhaa_race_edit_results',
            array($this,'bhaa_race_edit_results'));
        // make first element null to hide - 'edit.php?post_type=race'
        add_submenu_page( null, 'Edit Result', 'Edit Result',
            'manage_options', 'bhaa_race_edit_result',
            array($this,'bhaa_race_edit_result'));
    }

    function bhaa_race_edit_results() {
        //error_log('bhaa_race_edit_results '.$_GET['id']);
        $raceResult = new RaceResult();
        $res = $raceResult->getRaceResults($_GET['id']);
        // call the template
        $mustache = new Mustache();
        $raceResultTable = $mustache->renderTemplate(
                        'edit.race.results.individual',
                        array(
                            'runners'=>$res,
                            'isAdmin'=>true,
                            'formUrl'=>'link',
                            'racename'=>'racename',
                            'dist'=>'dist',
                            'unit'=>'unit',
                            'type'=>'type'));
        set_query_var( 'raceResultTable', $raceResultTable );
        include plugin_dir_path( __FILE__ ) . 'partials/race/edit_results.php';
    }

    function bhaa_race_edit_result() {
        //error_log('bhaa_race_edit_result');
        $raceResult = RaceResult::get_instance()->getRaceResult($_GET['raceresult']);
        $link = admin_url('admin.php'); // do we need the raceresult id?
        $raceLink = $this->generate_edit_raceresult_link($raceResult->race);
        include plugin_dir_path( __FILE__ ) . 'template/bhaa_race_edit_result.php';
    }

    public function bhaa_race_result_save() {
        error_log("bhaa_race_result_save() ".$_POST['bhaa_race']);
        //$raceResult = new RaceResult();
        RaceResult::get_instance()->updateRunnersRaceResultStandard(
            $_POST['bhaa_raceresult_id'],
            $_POST['bhaa_race'],
            $_POST['bhaa_runner'],
            $_POST['bhaa_time'],
            $_POST['bhaa_pre_standard'],
            $_POST['bhaa_post_standard'],
            $_POST['bhaa_number']
        );
        RaceResult::get_instance()->updatePositions($_POST['bhaa_race']);
        wp_redirect(admin_url('edit.php?post_type=race&page=bhaa_race_edit_results&id='.$_POST['bhaa_race']));
    }

    public function bhaa_race_result_delete() {
        error_log("bhaa_race_result_delete() ".$_POST['bhaa_race']);
        $raceResult = RaceResult::get_instance();
        $raceResult->deleteRaceResult($_POST['bhaa_raceresult_id']);
        $raceResult->updatePositions($_POST['bhaa_race']);
        wp_redirect(admin_url('edit.php?post_type=race&page=bhaa_race_edit_results&id='.$_POST['bhaa_race']));
    }

    function bhaa_race_delete_results() {
        if(wp_verify_nonce($_GET['_wpnonce'],'bhaa_race_delete_results')) {
            $raceResult = new RaceResult();
            $raceResult->deleteRaceResults($_GET['post_id']);
            //queue_flash_message("bhaa_race_delete_results ".$_GET['post_id']);
            wp_redirect(wp_get_referer());
            exit();
        }
    }

    function bhaa_race_load_results() {
        if(wp_verify_nonce($_GET['_wpnonce'],'bhaa_race_load_results')) {
            $race = get_post($_GET['post_id']);
            $resultText = $race->post_content;
            $raceResult = new RaceResult();
            $raceResult->processRaceResults($race->ID, $race->post_content);
            wp_redirect(wp_get_referer());
            exit();
        }
    }

    function bhaa_race_positions() {
        $raceResult = new RaceResult();
        $raceResult->updatePositions($_GET['post_id']);
        queue_flash_message("bhaa_race_positions");
        wp_redirect( $_SERVER['HTTP_REFERER'] );
        exit();
    }
    function bhaa_race_pace() {
        $raceResult = new RaceResult();
        $raceResult->updateRacePace($_GET['post_id']);
        queue_flash_message("bhaa_race_pace ".$_GET['post_id']);
        wp_redirect( $_SERVER['HTTP_REFERER'] );
        exit();
    }
    function bhaa_race_pos_in_cat() {
        $raceResult = new RaceResult();
        $raceResult->updateRacePosInCat($_GET['post_id']);
        queue_flash_message("bhaa_race_pos_in_cat");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_pos_in_std() {
        $raceResult = new RaceResult();
        $raceResult->updateRacePosInStd($_GET['post_id']);
        queue_flash_message("bhaa_race_pos_in_std");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_update_standards() {
        $raceResult = new RaceResult();
        $raceResult->updatePostRaceStd($_GET['post_id']);
        queue_flash_message("bhaa_race_update_standards");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_league() {
        $raceResult = new RaceResult();
        $raceResult->updateLeague($_GET['post_id']);
        queue_flash_message("bhaa_race_league");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_all() {
        $raceResult = new RaceResult();
        $raceResult->updateAll($_GET['post_id']);
        queue_flash_message("bhaa_race_all");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_delete_team_results(){
        error_log('bhaa_race_delete_team_results');
        $teamResult = new TeamResult($_GET['post_id']);
        $teamResult->deleteResults();
        queue_flash_message("bhaa_race_delete_team_results");
        wp_redirect(wp_get_referer());
        exit();
    }
    function bhaa_race_load_team_results() {
        $teamResult = new TeamResult($_GET['post_id']);
        $teamResultBlob = get_post_meta($_GET['post_id'],RaceCpt::BHAA_RACE_TEAM_RESULTS,true);
        $teamResults = explode("\n",$teamResultBlob);
        error_log('Number of team results '.sizeof($teamResults));
        foreach($teamResults as $result){
            $details = explode(',',$result);
            $teamResult->addResult($details);
        }
        queue_flash_message("bhaa_race_load_team_results :: ".sizeof($teamResults));
        wp_redirect(wp_get_referer());
        exit();
    }

    function bhaa_raceday_export() {
        Raceday::get_instance()->export();
        exit();
    }

    /**
     * Export the RaceMaster with data for this event.
     */
    function bhaa_race_export_racemaster() {
        RaceMaster::get_instance()->bhaa_admin_racemaster_export();
        queue_flash_message("bhaa_race_export_racemaster");
        wp_redirect(wp_get_referer());
        exit();
    }

    /**
     * Used to add an empty race result for a specific race.
     */
    function bhaa_race_add_result() {
        $newRaceResult = RaceResult::get_instance()->addDefaultResult($_POST['post_id']);
        queue_flash_message("bhaa_race_add_result");
        $url = admin_url('edit.php?post_type=race&page=bhaa_race_edit_result&raceresult='.$newRaceResult);
        wp_redirect($url);
    }

    function bhaa_race_post_row_actions($actions, $post) {
        if ($post->post_type =="race") {
            $actions = array_merge($actions,$this->get_admin_url_links($post));
        }
        return $actions;
    }

    function bhaa_register_race_cpt() {
        $raceLabels = array(
            'name' => _x( 'Races', 'race' ),
            'singular_name' => _x( 'Race', 'race' ),
            'add_new' => _x( 'Add New', 'race' ),
            'add_new_item' => _x( 'Add New Race', 'race' ),
            'edit_item' => _x( 'Edit race', 'race' ),
            'new_item' => _x( 'New race', 'race' ),
            'view_item' => _x( 'View race', 'race' ),
            'search_items' => _x( 'Search races', 'race' ),
            'not_found' => _x( 'No races found', 'race' ),
            'not_found_in_trash' => _x( 'No races found in Trash', 'race' ),
            'parent_item_colon' => _x( 'Parent event:', 'event' ),
            'menu_name' => _x( 'BHAA Races', 'race' ),
        );

        $raceArgs = array(
            'labels' => $raceLabels,
            'hierarchical' => false,
            'description' => 'BHAA Race',
            'supports' => array('title','excerpt','editor'),
            'public' => true,
            'show_ui' => true,
            // http://wordpress.stackexchange.com/questions/110562/is-it-possible-to-add-custom-post-type-menu-as-another-custom-post-type-sub-menu
            'show_in_menu' => true,//'edit.php?post_type=house'
            'show_in_nav_menus' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'query_var' => false,
            'can_export' => true,
            'publicly_queryable' => true,
            'rewrite' => true,//array('slug' => 'race'),//,'with_front' => false),
            'capability_type' => 'post'
        );
        register_post_type( 'race', $raceArgs );
    }

    function bhaa_manage_race_posts_columns( $column ) {
        return array(
            'title' => __('Title'),
            'distance' => __('Distance'),
            //'id' => __('ID'),
            'type' => __('Type'),
            //'count' => __('Count'),
            'date' => __('Date')
        );
    }

    function bhaa_manage_race_posts_custom_column( $column, $post_id ) {
        switch ($column) {
            case 'distance' :
                echo get_post_meta($post_id,RaceCpt::BHAA_RACE_DISTANCE,true).''.get_post_meta($post_id,RaceCpt::BHAA_RACE_UNIT,true);
                break;
            case 'type' :
                echo get_post_meta($post_id,RaceCpt::BHAA_RACE_TYPE,true);
                break;
            default:
        }
    }

    /**
     * Register the race meta box
     */
    public function bhaa_race_meta_data() {
        add_meta_box(
            'bhaa-race-meta',
            __( 'Race Details', 'bhaa-race-meta' ),
            array($this, 'bhaa_race_meta_fields'),
            'race',
            'side',
            'high'
        );
    }

    public function bhaa_team_meta_data() {
        add_meta_box(
            'bhaa-race-team-meta',
            __( 'Team Results', 'bhaa-race-team-meta' ),
            array($this, 'bhaa_race_team_result_textarea'),
            'race',
            'normal',
            'high'
        );
    }

    function bhaa_race_meta_fields( $post ) {
        //wp_nonce_field( plugin_basename( __FILE__ ), 'bhaa_race_meta_data' );

        $distance = get_post_custom_values(RaceCpt::BHAA_RACE_DISTANCE, $post->ID);
        echo '<p>Distance <input type="text" name='.RaceCpt::BHAA_RACE_DISTANCE.' value="'.$distance[0].'" /></p>';

        // http://stackoverflow.com/questions/3507042/if-block-inside-echo-statement
        $unit = get_post_custom_values(RaceCpt::BHAA_RACE_UNIT, $post->ID);
        echo '<p>Unit <select name='.RaceCpt::BHAA_RACE_UNIT.'>';
        echo '<option value="Mile" '.(($unit[0]=='Mile')?'selected="selected"':"").'>Mile</option>';
        echo '<option value="Km" '.(($unit[0]=='Km')?'selected="selected"':"").'>Km</option>';
        echo '</select></p>';

        $type = get_post_custom_values(RaceCpt::BHAA_RACE_TYPE, $post->ID);
        echo '<p>Type <select name='.RaceCpt::BHAA_RACE_TYPE.'>';
        echo '<option value="C" '.(($type[0]=='C')?'selected="selected"':"").'>C</option>';
        echo '<option value="M" '.(($type[0]=='M')?'selected="selected"':"").'>M</option>';
        echo '<option value="W" '.(($type[0]=='W')?'selected="selected"':"").'>W</option>';
        echo '<option value="S" '.(($type[0]=='S')?'selected="selected"':"").'>S</option>';
        echo '<option value="TRACK" '.(($type[0]=='TRACK')?'selected="selected"':"").'>TRACK</option>';
        echo '</select></p>';

        echo implode('<br/>', $this->get_admin_url_links($post));
    }

    function bhaa_race_team_result_textarea( $post ) {
        $teamresults = get_post_meta($post->ID,RaceCpt::BHAA_RACE_TEAM_RESULTS,true);
        echo '<textarea name='.RaceCpt::BHAA_RACE_TEAM_RESULTS.' id='.RaceCpt::BHAA_RACE_TEAM_RESULTS.'
			 rows="20" cols="80" style="width:99%">'.$teamresults.'</textarea>';
        //echo '<textarea rows="20" cols="80" name='.RaceCpt::BHAA_RACE_TEAM_RESULTS.' value="'.$teamresults.'" />';
    }

    /**
     * Save the race meta data
     * @param unknown_type $post_id
     */
    public function bhaa_save_race_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        if ( empty( $_POST ) )
            return;

        if ( !empty($_POST[RaceCpt::BHAA_RACE_DISTANCE])) {
            update_post_meta( $post_id, RaceCpt::BHAA_RACE_DISTANCE, $_POST[RaceCpt::BHAA_RACE_DISTANCE] );
            error_log($post_id .' -> bhaa_race_distance -> '.$_POST[RaceCpt::BHAA_RACE_DISTANCE]);
        }

        if ( !empty($_POST[RaceCpt::BHAA_RACE_UNIT])) {
            error_log($post_id .' -> bhaa_race_distance -> '.$_POST[RaceCpt::BHAA_RACE_UNIT]);
            update_post_meta( $post_id, RaceCpt::BHAA_RACE_UNIT, $_POST[RaceCpt::BHAA_RACE_UNIT] );
        }

        if ( !empty($_POST[RaceCpt::BHAA_RACE_TYPE])) {
            error_log($post_id .' -> '.RaceCpt::BHAA_RACE_TYPE.' -> '.$_POST[RaceCpt::BHAA_RACE_TYPE]);
            update_post_meta( $post_id, RaceCpt::BHAA_RACE_TYPE, $_POST[RaceCpt::BHAA_RACE_TYPE] );
        }

        if ( !empty($_POST[RaceCpt::BHAA_RACE_TEAM_RESULTS])) {
            error_log($post_id .' -> '.RaceCpt::BHAA_RACE_TEAM_RESULTS.' -> '.$_POST[RaceCpt::BHAA_RACE_TEAM_RESULTS]);
            update_post_meta( $post_id, RaceCpt::BHAA_RACE_TEAM_RESULTS, $_POST[RaceCpt::BHAA_RACE_TEAM_RESULTS] );
        }
    }

    function get_admin_url_links($post) {
        return array(
            'bhaa_race_delete_results' => $this->generate_race_admin_url_link('bhaa_race_delete_results',$post->ID,'Delete Results'),
            'bhaa_race_load_results' => $this->generate_race_admin_url_link('bhaa_race_load_results',$post->ID,'Load Results'),
            'bhaa_race_edit_results' => $this->generate_edit_raceresult_link($post->ID)
            //'bhaa_race_positions' => $this->generate_admin_url_link('bhaa_race_positions',$post->ID,'Positions'),
            //'bhaa_race_pace' => $this->generate_admin_url_link('bhaa_race_pace',$post->ID,'Pace'),
            //'bhaa_race_pos_in_cat' => $this->generate_admin_url_link('bhaa_race_pos_in_cat',$post->ID,'Pos_in_cat'),
            //'bhaa_race_pos_in_std' => $this->generate_admin_url_link('bhaa_race_pos_in_std',$post->ID,'Pos_in_std'),
            //'bhaa_race_update_standards' => $this->generate_admin_url_link('bhaa_race_update_standards',$post->ID,'Update Stds'),
            //'bhaa_race_league' => $this->generate_admin_url_link('bhaa_race_league',$post->ID,'League Points'),
            //'bhaa_race_all' => $this->generate_admin_url_link('bhaa_race_all',$post->ID,'All'),
            //'bhaa_race_delete_team_results' => $this->generate_admin_url_link('bhaa_race_delete_team_results',$post->ID,'Delete Teams'),
            //'bhaa_race_load_team_results' => $this->generate_admin_url_link('bhaa_race_load_team_results',$post->ID,'Load Teams'),
            //'bhaa_race_edit_results' => $this->generate_edit_raceresult_link($post->ID),
            //'bhaa_race_export_racemaster' => $this->generate_admin_url_link('bhaa_race_export_racemaster',$post->ID,'Export RaceMaster')
        );
    }

    private function generate_race_admin_url_link($action,$post_id,$link_title) {
        $adminURL = add_query_arg(
            array('action'=>$action,
                'post_type'=>'race',
                'post_id'=>$post_id
            ),admin_url());
        return '<a href='.wp_nonce_url($adminURL, $action).'>'.$link_title.'</a>';
    }

    /**
     * Return a edit link to a specific set of race results
     */
    private function generate_edit_raceresult_link($post_id) {
        $editURL = add_query_arg(
            array('page'=>'bhaa_race_edit_results',
                'post_type'=>'race',
                'id'=>$post_id
            ),admin_url('edit.php'));
        return '<a href='.$editURL.'>Edit Results</a>';
    }
}