<?php
/**
 * Author: Petr Havelka
 * Author URI: www.web-entity.cz
 * Version: 0.1b
 * License: MIT
 */


/************************************************
 * Config
*/

// table columns 'name' => array( 'column width', 'color (css)')
// comment or comment out for show/hide column
$columns = array(
  //'priority' => array('30','rgb(174, 183, 188)'),
  'projectName' => array('','rgb(252, 107, 0)'),
  'stage' => array('0','rgb(0, 159, 153)'),
  'dueDate' => array('','rgb(0, 108, 230)'),
  //'category' => array('',''),
  //'owner' => array('','')
);

// category filter - what categories to show
define('FILTER_BY_CATEGORY', false);  //set to true if you whant to filter by categories
$categories = array(
  'category1',
  'category2'
);

// owner filter - show project with these owners
define('FILTER_BY_OWNER', false); //set to true if you whant to filter by owner
$owners = array(
  'owner1',
  'owner2'
);

// order by priority - default is by due date
define('ORDER_BY_PRIORITY', false); //set to true if you whant to use it

// min days to show as red
$redDays = 7;
$redColor = "red";

//owners nicknames
define('USE_NICKNAMES', false); //set to true if you whant to use it
$nicknames = array(
  'Mary Jennings' => 'Mary'
);


//Date format for dueDate - must be same as Daylites ouput - see http://php.net/manual/en/function.date.php
$dateFormat = "j. n. Y";

// shot for example 1 month, 2 days instead of 10.5.2013. You must have correct $dateFormat .
define('SHOW_TO_DATE', false); //set to true if you whant to use it

/************************************************
 */


// load data from applescript and save to dayliteProjects.json
if(isset($_GET['load'])) {
  $handle = fopen('php://input','r');
  $jsonInput = fgets($handle);
  file_put_contents("dayliteProjects.json", $jsonInput , LOCK_EX);
} else {

  //or show table for status board
  table();

}


function table(){
  //load configs
  global $columns;
  global $categories;
  global $owners;
  global $nicknames;

	//load data from file
	$data = json_decode( file_get_contents("dayliteProjects.json"), true );

	// order data by priority
	if(ORDER_BY_PRIORITY) {
  	if(!empty($data)) {
  	  usort($data, 'priority_sort');
    }
	}

	/*echo '<pre>';
	var_dump($data);
  echo '</pre>';*/

	?>
	<table id="projects">
		<?php

		// test if data exist
		if( !empty($data) ) {

  		//list all projects
  		foreach( $data as $project => $data ) {

    		//filter by category
    		if(FILTER_BY_CATEGORY && !in_array($data['category'], $categories)) {
      		continue;
    		}

    		//filter by owner
    		if(FILTER_BY_OWNER && !in_array($data['owner'], $owners)) {
      		continue;
    		}

  		  //start row
  		  echo '<tr>';

  		  //show only selected properties
  		  foreach ($columns as $name => $type) {

    		  //default values
  		    $content = $data[$name];
  		    $width = $type[0];
  		    $color = $type[1];

  		    //get proper output by type
    		  switch ($name) {
            case 'owner':
              if(USE_NICKNAMES && array_key_exists($content, $nicknames)) {
                $content = $nicknames[$content];
              }
              break;
            case 'dueDate':
              if( $due = get_date_content($data[$name]) ) {
                $content = $due[0];
                if( !empty($due[1]) ) { $color = $due[1]; }
              }
              break;
            default:

          }

          //setup color
          if(!empty($color)) {
            $color = 'color: '.$color.';';
          }

          if(!empty($width)) {
            $width = 'width: '.$width.'px;';
          }

          //print project column
          printf('<td class="%s" style="text-transform: none; %s %s">%s</td>',$name, $color, $width ,$content);

  		  }

  		  //end row
  		  echo '</tr>';
  		}

		} else {
		?>
		  <tr><td>No projects.</td></tr>
		<?php } ?>
	</table>

	<?php
}

//date helper function
function get_date_content($date) {
  if(!empty($date)) {
    global $dateFormat;
    global $redDays;
    global $redColor;

    $due = DateTime::createFromFormat($dateFormat, $date);
    $curr = new DateTime();
    $diff = $due->diff($curr);


    $years = $diff->format('%y');
    $months = $diff->format('%m');
    $days = $diff->format('%d');

    $color = "";
    if($years!=0){
      $to = $years.' year(s) '. $months.' month(s) '.$days.' day(s)';
    } else if($months != 0) {
      $to = $months.' month(s) '.$days.' day(s)';
    } else if($days != 0 ) {
      $to = $days.' day(s)';

      if(($redDays >= $days && $months == 0 && $years == 0 && $diff->invert) || !$diff->invert) {
        $color = $redColor;
      }

    }

    if(!$diff->invert) {
      $to .= ' ago';
    }

    if(!SHOW_TO_DATE) {
      $to = $date;
    }

    return array($to,$color);

  } else {
    return false;
  }
}

//helper function to sort by priority
function priority_sort($a, $b) {
  return ($b['priority'] > $a['priority']);
}

?>

