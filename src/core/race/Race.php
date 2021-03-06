<?php
/**
 * Created by IntelliJ IDEA.
 * User: e074820
 * Date: 20/04/2018
 * Time: 16:38
 */

namespace BHAA\core\race;


class Race {

    var $race_id;

    const BHAA_RACE_DISTANCE = 'bhaa_race_distance';
    const BHAA_RACE_UNIT = 'bhaa_race_unit';
    const BHAA_RACE_TYPE = 'bhaa_race_type';

    function __construct($race_id) {
        $this->race_id = $race_id;
    }

    function getDistance() {
        return (int) get_post_meta($this->race_id,Race::BHAA_RACE_DISTANCE,true);
    }

    function getUnit() {
        return get_post_meta($this->race_id,Race::BHAA_RACE_UNIT,true);
    }

    function getKmDistance() {
        if(strpos($this->getUnit(), 'Mile') === 0)
            return $this->getDistance() * 1.609344;
        else
            return $this->getDistance();
    }

    function getTitle() {
        return get_the_title($this->race_id).' '.$this->getDistance().' '.$this->getUnit();
    }

    function getDate(){
        global $wpdb;
        $SQL = $wpdb->prepare('SELECT eventdate FROM wp_bhaa_race_detail WHERE race=%d LIMIT 1',$this->race_id);
        $res = $wpdb->get_var($SQL);
        return $res;
    }
}