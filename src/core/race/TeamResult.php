<?php
/**
 * Created by IntelliJ IDEA.
 * User: e074820
 * Date: 24/04/2018
 * Time: 15:41
 */

namespace BHAA\core\race;


class TeamResult {

    private $wpdb;
    private $race;

    function __construct($race) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->race = $race;
    }

    public function getTableName() {
        return 'wp_bhaa_teamresult';
    }

    public function addResult($row) {
        // calculate the team league points.
        $leaguepoints = 7 - $row[1];
        if($leaguepoints<=1){
            $leaguepoints=1;
        }

        $res = $this->wpdb->insert(
            $this->getTableName(),
            array(
                'race'=>$this->race,
                'class'=>$row[0],
                'position'=>$row[1],
                'teamname'=>substr($row[2],0,19),
                'team'=>$row[3],
                'totalpos'=>$row[4],
                'totalstd'=>$row[5],
                'runner'=>$row[6],
                'pos'=>$row[7],
                'std'=>$row[8],
                'racetime'=>$row[9],
                'company'=>$row[3],
                'companyname'=>substr($row[2],0,19),
                'leaguepoints'=> $leaguepoints)
        );
    }

    public function deleteResults() {
        return $this->wpdb->delete(
            $this->getTableName(),
                array('race' => $this->race
            ));
    }

    function getTeamResults() {
        return $this->wpdb->get_results(
            $this->wpdb->prepare('select wp_bhaa_teamresult.*,wp_users.display_name from wp_bhaa_teamresult
				join wp_users on wp_users.id=wp_bhaa_teamresult.runner
				where race=%d order by class, position, pos',$this->race)
        );
    }

    public function getHouseResults($team,$limit=30) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare('select race.post_title,tr.class,
			  tr.position,MAX(tr.leaguepoints) as leaguepoints f
				rom wp_bhaa_teamresult tr
				join wp_posts race on tr.race=race.id
				where team=%d
				group by team,race
				order by race asc
				limit %d',$team,$limit)
        );
    }

    /**
     * We'll do the html table generation here for the moment.
     */
    public function getRaceTeamResultTable() {
        $results = $this->getTeamResults();
        //$table = '<h2 race="'.$this->race.'">Team Results</h2>';
        //$table .= $this->teamSummary();
        $table = $this->displayClassTable($results,'A');
        $table .= $this->displayClassTable($results,'B');
        $table .= $this->displayClassTable($results,'C');
        $table .= $this->displayClassTable($results,'D');
        $table .= $this->displayClassTable($results,'W');
        //$table .= '<br/>';
        return $table;
    }

    private function displayClassTable($results,$class) {

        // ["id"]=> string(2) "64" ["race"]=> string(4) "2595" ["class"]=> string(1) "W" ["position"]=> string(1) "1"
        // ["team"]=> string(2) "21" ["teamname"]=> string(20) "Accountants Ladies A" ["totalpos"]=> string(2) "48"
        // ["totalstd"]=> string(2) "45" ["runner"]=> string(4) "7048" ["pos"]=> string(2) "11" ["std"]=> string(2) "15"
        // ["racetime"]=> string(8) "00:13:32" ["company"]=> string(3) "549" ["companyname"]=> string(18) "McInerney Saunders"
        // ["leaguepoints"]=> string(1) "0" }
        $table = '';
        $header='';
        $position=0;
        $count=0;
        foreach($results as $row)
        {
            if($row->class==$class) {
                //var_dump($row);
                if($row->class!=$header) {
                    $header = $row->class;
                    $position = $row->position;
                    //$table .= $this->generateRow('<h4><b>Class '.$row->class.'</b></h4>','','','','');
                    $table .= '<h3><b>Class '.$row->class.'</b></h3>';

                }

                //first row of a new team
                if($count==0) {
                    $table .= '<table class="table borderless fixed" width="90%">';
                    $position = $row->position;
                    // start table
                    $house_url = sprintf('<a href="/?post_type=house&p=%d"><b>%s</b></a>',$row->team,$row->teamname);
                    $table .= $this->generateHeaderRow('<b>'.$row->class.$row->position.' -> '.$house_url.'</b>','','','<b>Position</b>','<b>Standard</b>','');
                    // add first row
                    $table .= $this->generateHeaderRow('Athlete','Time','Company',$row->totalpos,$row->totalstd);
                }

                $runner_url = sprintf('<a href="./runner/?id=%s"><i>%s</i></a>',$row->runner,$row->display_name);
                $table .= $this->generateRow($runner_url,$row->racetime,$row->companyname,$row->pos,$row->std);
                $count++;
                if($count==3) {
                    // add second/third row
                    $count = 0;
                    $position = 0;
                    $table .= '</table>';
                }
            }
        }
        return $table;
    }

    public function generateHeaderRow($name='',$time='',$company='',$position='',$standard='',$style='') {
        return sprintf('<tr class="%s">
			<!-- bgcolor="#FF0000" -->
			<th style="width: 50px;">%s</th>
			<th style="width: 50px;">%s</th>
			<th style="width: 50px;">%s</th>
			<th style="width: 20px;">%s</th>
			<th style="width: 20px;">%s</th>
			</tr>',$style,$name,$time,$company,$position,$standard);
    }

    public function generateRow($name='',$time='',$company='',$position='',$standard='',$style='') {
        return sprintf('<tr class="%s">
			<td style="width: 50px;">%s</td>
			<td style="width: 50px;">%s</td>
			<td style="width: 50px;">%s</td>
			<td style="width: 20px;">%s</td>
			<td style="width: 20px;">%s</td>
			</tr>',$style,$name,$time,$company,$position,$standard);
    }

    /**
     * Add 6 league points to the team
     */
    public function addTeamOrganiserPoints($team) {
        return $this->wpdb->insert(
            $this->getTableName(),
            array('race' => $this->race,
                'team' => $team,
                'leaguepoints' => 6,
                'class' => 'R'));
    }

    /**
     * Delete assigned league points
     */
    public function deleteTeamOrganiserPoints($team) {
        return $this->wpdb->delete(
            $this->getTableName(),
            array('race' => $this->race,
                'team' => $team,
                'leaguepoints' => 6,
                'class' => 'R'));
    }

    function getTeamResultsByEvent($event) {
        $query = $this->wpdb->prepare('
			SELECT wp_bhaa_teamresult.*,wp_posts.post_title as teamname FROM wp_bhaa_teamresult
			join wp_posts on wp_posts.post_type="house" and wp_bhaa_teamresult.team=wp_posts.id
			where race IN (select p2p_to from wp_p2p where p2p_from=%d)
			order by class, positiontotal',$event);
        $this->items = $this->wpdb->get_results($query,ARRAY_A);
    }
}