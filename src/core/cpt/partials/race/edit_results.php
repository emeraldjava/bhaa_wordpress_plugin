<?php
$link = admin_url('admin.php');
$race = get_the_title($_GET['id']);
$edit = get_edit_post_link($_GET['id']);
echo '<h2>Edit Race Results : <a href="'.$edit.'">'.$race.'</a></h2>';
echo '<div class="wrap">';
//    <form method="post" action="'.$link.'">
//	<input type="hidden" name="action" value="bhaa_race_add_result" />
//		<input type="hidden" name="post_id" value="'.$_GET['id'].'"/>
//		<input type="submit" value="Add Race Result"/>
//	</form>
//    <hr/>';
    echo get_query_var('raceResultTable');
    echo '</div>';
?>