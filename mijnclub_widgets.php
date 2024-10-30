<?php

class MijnClubWedstrijden extends WP_Widget
{
  function MijnClubWedstrijden()
  {
    $widget_ops = array('classname' => 'MijnClubWedstrijden', 'description' => 'Toont de eerstvolgende x aantal wedstrijden van de hele club' );
    $this->WP_Widget('MijnClubWedstrijden', 'MijnClub Eerstvolgende Wedstrijden', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Eerstvolgende Wedstrijden' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	
	$devOptions = mijnclub_getOptions();
	
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT

	if (mijnclub_authenticate('widget-wedstrijden')) {
	//loads wedstrijden of entire periode
	$xmlurl = 'http://www.mijnclub.nu/clubs/speelschema/xml/'.$devOptions['clubcode'].'/periode,/';
	$xml = mijnclub_loadxml($xmlurl);
	$wedstrijden = $xml->wedstrijden;
	
	$now = time()+7200; //hardcoded timezone difference = +2hours

	$wedstrijdarray = array(); //masterarray
	
	//puts alle wedstrijden in array
	foreach ($wedstrijden->wedstrijd as $wedstrijd) { //loops through all wedstrijden
		$datum = $wedstrijd->datum;
		$aanvang = $wedstrijd->aanvang;
		$wedatts = $wedstrijd->attributes();
		$wedstrijdentry = array( //is an array which contains all the details of the wedstrijd
		'stamp' => strtotime($datum.' '.$aanvang),
		'datum' => date('j M \'y',strtotime($datum)),
		'aanvang' => $wedstrijd->aanvang,
		'aanwezig' => $wedstrijd->aanvang->attributes(),
		'thuisteam' => $wedstrijd->thuisteam,
		'uitteam' => $wedstrijd->uitteam,
		'lokatie' => $wedatts['lokatie'],
		'afgelast' => $wedatts['afgelast'],
		);
		$wedstrijdarray[] = $wedstrijdentry; //puts array of wedstrijddetails in masterarray
	}
	
	foreach ($wedstrijdarray as $stamp) { //puts the timestamps in a simple array so it can be sorted
		$stamparray[] = $stamp['stamp'] ;
	}
	if (isset($stamparray) && $stamparray != null){
		asort($stamparray); //sorts the timestamps
		$s = array_keys($stamparray); //makes new array which has the order of keys of timestamps
	}
	
	$start=-1;
	if (isset($stamparray)) {
		$max = count($stamparray)-1;
	} else {
		$max = -1;
	}
	for ($j=$max; $j >= 0; $j--) { //starts at latest stamp and works up to earliest and breaks the forloop when a timestamp is found that is earlier than the current time, and uses that timestamp 
		if ($now > $stamparray[$s[$j]]) {
			break;
		}
		$start = $j; //sets the first wedstrijd to print starting at $start
	}

	$max = count($wedstrijdarray); //prints all wedstrijden that are found starting from $start
	$aantal = $devOptions['aantalwedstrijden']; //retrieves value 
	$currentdate = '';
	
	if ($start >= 0 ) {
		echo '<div class="mijnclub-widget-alle-wedstrijden">';
		for ($i=$start; $i < $max; $i++,$aantal--) {
			if ($aantal > 0) { //only prints wedstrijd if aantal is still > 0
				if ($currentdate != $wedstrijdarray[$s[$i]]['datum']) {
					echo '<h4 class="datum">'.$wedstrijdarray[$s[$i]]['datum'].'</h4>';
					$currentdate = $wedstrijdarray[$s[$i]]['datum'];
				}
				echo '<div class="mijnclub-widget-alle-wedstrijden-inner">';
				echo 'Aanvang: '.$wedstrijdarray[$s[$i]]['aanvang'];
				if ($wedstrijdarray[$s[$i]]['afgelast'] == 'ja') {
					echo " <span class=\"afgelast\">&#187; AFGELAST &#171;</span>";
				}
				echo '<p>';
				if ($wedstrijdarray[$s[$i]]['lokatie']=='thuis') {
					echo '<strong>'.$wedstrijdarray[$s[$i]]['thuisteam'].'</strong>';
					echo ' - '.$wedstrijdarray[$s[$i]]['uitteam'];
				} elseif ($wedstrijdarray[$s[$i]]['lokatie']=='uit') {
					echo $wedstrijdarray[$s[$i]]['thuisteam'].' - ';
					echo '<strong>'.$wedstrijdarray[$s[$i]]['uitteam'].'</strong>';
				}
				echo '</p>';
				echo '</div>';
			}
		}
		echo '</div>';
	} else {
		echo 'Geen wedstrijden gevonden';
	}
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}

	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubWedstrijden");') );

class MijnClubEersteTeamWedstrijd extends WP_Widget
{
  function MijnClubEersteTeamWedstrijd()
  {
    $widget_ops = array('classname' => 'MijnClubEersteTeamWedstrijd', 'description' => 'Toont de eerstvolgende wedstrijd van het 1e team' );
    $this->WP_Widget('MijnClubEersteTeamWedstrijd', 'MijnClub Eerstvolgende Wedstrijd Eerste', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Eerstvolgende wedstrijd Eerste' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	
	$devOptions = mijnclub_getOptions();
	
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT

	if (mijnclub_authenticate('widget-eersteteam')) {
	//loads wedstrijden of entire periode
	$xmlurl = 'http://www.mijnclub.nu/clubs/speelschema/xml/'.$devOptions['clubcode'].'/periode,/team/'.$devOptions['eersteteam'];
	$xml = mijnclub_loadxml($xmlurl);
	$wedstrijden = $xml->wedstrijden;
	
	$now = time()+7200; //hardcoded timezone difference

	$wedstrijdarray = array(); //masterarray
	
	//puts alle wedstrijden in array
	foreach ($wedstrijden->wedstrijd as $wedstrijd) { //loops through all wedstrijden
		$datum = $wedstrijd->datum;
		$aanvang = $wedstrijd->aanvang;
		$wedatts = $wedstrijd->attributes();
		$wedstrijdentry = array( //is an array which contains all the details of the wedstrijd
		'stamp' => strtotime($datum.' '.$aanvang),
		'datum' => date('j M \'y',strtotime($datum)),
		'aanvang' => $wedstrijd->aanvang,
		'aanwezig' => $wedstrijd->aanvang->attributes(),
		'thuisteam' => $wedstrijd->thuisteam,
		'uitteam' => $wedstrijd->uitteam,
		'lokatie' => $wedatts['lokatie'],
		);
		$wedstrijdarray[] = $wedstrijdentry; //puts array of wedstrijddetails in masterarray
	}
	
	foreach ($wedstrijdarray as $stamp) { //puts the timestamps in a simple array so it can be sorted
		$stamparray[] = $stamp['stamp'] ;
	}
	if (isset($stamparray) && $stamparray != null){
		asort($stamparray); //sorts the timestamps
		$s = array_keys($stamparray); //makes new array which has the order of keys of timestamps
	}
	
	$start=-1;
	if (isset($stamparray)) {
		$max = count($stamparray)-1;
	} else {
		$max = -1;
	}
	for ($j=$max; $j >= 0; $j--) { //starts at latest stamp and works up to earliest and breaks the forloop when a timestamp is found that is earlier than the current time, and uses that timestamp 
		if ($now > $stamparray[$s[$j]]) {
			break;
		}
		$start = $j; //sets the first wedstrijd to print starting at $start
	}
	
	if ($start >= 0 ) {
		echo '<div class="mijnclub-widget-eersteteam-wedstrijd">';
		echo '<h4 class="datum">'.$wedstrijdarray[$s[0]]['datum'].'</h4>';

		echo 'Aanvang: '.$wedstrijdarray[$s[0]]['aanvang'].'<br>';
		if ($wedstrijdarray[$s[0]]['lokatie']=='thuis') {
			echo '<strong>'.$wedstrijdarray[$s[0]]['thuisteam'].'</strong>';
			echo ' - '.$wedstrijdarray[$s[0]]['uitteam'];
		} elseif ($wedstrijdarray[$s[0]]['lokatie']=='uit') {
			echo $wedstrijdarray[$s[0]]['thuisteam'].' - ';
			echo '<strong>'.$wedstrijdarray[$s[0]]['uitteam'].'</strong>';
		}
		echo '</div>';
	} else {
		echo 'Geen wedstrijden gevonden';
	}
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}

	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubEersteTeamWedstrijd");') );

class MijnClubTeamselect extends WP_Widget
{
  function MijnClubTeamselect()
  {
    $widget_ops = array('classname' => 'MijnClubTeamselect', 'description' => 'Toont een dropdown menu om een team te selecteren' );
    $this->WP_Widget('MijnClubTeamselect', 'MijnClub Teamselector', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Ga naar team' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT
	
	if (mijnclub_authenticate('widget-teamselector')) {
		$allteams = mijnclub_get_page('Alle teams');
		$meta = get_post_meta($allteams->ID, 'mijnclub', true);
		if ($allteams != NULL && $meta == 'true') { //checks if 'Alle teams' page exists and is a mijnclub page
			$teampageid = mijnclub_get_page('Teams')->ID;
			$mijnclubpage = get_post_meta($teampageid,'mijnclub',true);
			if ($teampageid != NULL && $mijnclubpage == 'true') {
				$output = '';

				if (isset($_COOKIE['gekozenteam'])) {
					$chosen = $_COOKIE['gekozenteam']; //reads previously chosen team from cookie
				}
				$output .= "<div class=\"mijnclub-widget-teamselector\">\n";
				$output .= "<form method=\"post\" action=\"\">\n";
				$output .= "<p>\n<select class=\"teamlist\" id=\"teamlist\" name=\"goto\">\n";
				if ($chosen=="") {
					$output .= "<option value=\"\" SELECTED></option>\n"; //empty option
				} 
				
				$args = array (
					'sort_order' => 'ASC',
					'sort_column' => 'menu_order',
					'hierarchical' => 0,
					'meta_key' => 'mijnclub',
					'meta_value' => 'true',
					'parent' => $teampageid,
					'post_type' => 'page',
					'post_status' => 'publish'
				);
				
				$cat = ''; //empty categorie at first team
				$first = true; //only true for first category
				
				$catpages = get_pages($args);
				foreach ($catpages as $c) {
					$cat = (string) $c->post_title;
					if ($cat != 'Alle Teams') { //dont print "Alle teams"
						if (!$first) {
							$output .= "</optgroup>\n";
						}
						$output .= "<optgroup label=\"".$cat."\">\n";
						$first = false;
						
						$args = array (
							'sort_order' => 'ASC',
							'sort_column' => 'menu_order',
							'hierarchical' => 0,
							'meta_key' => 'mijnclub',
							'meta_value' => 'true',
							'parent' => $c->ID,
							'post_type' => 'page',
							'post_status' => 'publish'
						);
						$teampages = get_pages($args);
						foreach($teampages as $t) {
							$naam = $t->post_title;
							$url = get_option('siteurl').'/?p='.$t->ID;
							$output .= "<option value=\"".$url."\" ".selected($naam,$chosen,false).">".$naam."</option>\n";
						}
					}
				}
				$output .= "</optgroup>\n</select>\n";
				$output .= "<input type=\"submit\" value=\"&#187;\"/>\n</p>\n";
				$output .= "</form>\n";
				$output .= "</div>\n";
				
				echo $output;
			} else {
				echo "'Teams' hoofdpagina niet gevonden! Teamselector kan niet geinitialiseerd worden";
			}
		} else {
			echo 'Teampaginas bestaan nog niet!';
		}
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}
	
	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubTeamselect");') );

class MijnClubStandEerste extends WP_Widget
{
  function MijnClubStandEerste()
  {
    $widget_ops = array('classname' => 'MijnClubStandEerste', 'description' => 'Toont de stand van het Eerste' );
    $this->WP_Widget('MijnClubStandEerste', 'MijnClub Stand van Eerste', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Stand Eerste' ) );
    $title = $instance['title'];
	$title = 'Stand Eerste';
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT
	if (mijnclub_authenticate('widget-standeerste')) {
	$devOptions = mijnclub_getOptions();
	$eersteteam = $devOptions['eersteteam'];
	$naameerste = $devOptions['eersteteamnaam'];

	
	//loads wedstrijden of entire periode
	$xmlurl = 'http://www.mijnclub.nu/clubs/teams/embed/'.$devOptions['clubcode'].'/team/'.$eersteteam.'?layout=stand&format=xml';

	
	$xml = mijnclub_loadxml($xmlurl);
	
	$teams = $xml->table[0]->tbody;
	
	if ($teams!=null) {	
		echo "<div class=\"mijnclub-widget-stand-eerste\">\n";
		echo "<table class=\"standeerste\" border=\"1\">\n
		<thead>\n<tr>\n
		<th class=\"positie\"><strong>#</strong></th>\n
		<th class=\"team\"><strong>Team</strong></th>\n
		<th class=\"gespeeld\"><strong>G</strong></th>\n
		<th class=\"punten\"><strong>P</strong></th>\n
		</tr>\n</thead>\n
		<tbody>\n";
		
		foreach ($teams->tr as $team) {
			$td = $team->td;
			$rank = $td[0];
			$teamnaam = $td[1];
			$played = $td[2];
			$points = $td[6];
			
			if ($teamnaam == $naameerste) {
				echo "<tr class=\"eersteteam\">\n
				<td>".$rank."</td>\n
				<td>".$teamnaam."</td>\n
				<td>".$played."</td>\n
				<td>".$points."</td>\n
				</tr>";
			} else {
				echo "<tr>\n
				<td>".$rank."</td>\n
				<td>".$teamnaam."</td>\n
				<td>".$played."</td>\n
				<td>".$points."</td>\n
				</tr>";
			}
		}
		echo "</tbody>\n</table>";
		echo '<p>G = Gespeeld, P = Punten</p>';
		echo '</div>';
	} else {
		echo 'Geen stand gevonden';
	}
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}
	
	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubStandEerste");') );

class MijnClubTrainingen extends WP_Widget
{
  function MijnClubTrainingen()
  {
    $widget_ops = array('classname' => 'MijnClubTrainingen', 'description' => 'Toont de trainingen van vandaag van de hele club' );
    $this->WP_Widget('MijnClubTrainingen', 'MijnClub Eerstvolgende Trainingen', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => 'Trainingen van vandaag' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	
	$devOptions = mijnclub_getOptions();
	
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT

	if (mijnclub_authenticate('widget-trainingen')) {
		$now = time()+7200; //GMT conversion
		$day = date('l',$now); //converts timestamp to english day name
		switch ($day) { //converts english dayname to dutch name
			case 'Monday': $dag = 'maandag';break;
			case 'Tuesday': $dag = 'dinsdag';break;
			case 'Wednesday': $dag = 'woensdag';break;
			case 'Thursday': $dag = 'donderdag';break;
			case 'Friday': $dag = 'vrijdag';break;
			case 'Saturday': $dag = 'zaterdag';break;
			case 'Sunday': $dag = 'zondag';break;
			default : $dag = 'maandag';break;
		}
		$atts = array('dag' => $dag);
		echo mijnclub_printtrainingen($atts, false); //false param specifies not to print the day-selector
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}

	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubTrainingen");') );

class MijnClubUitslagen extends WP_Widget
{
  function MijnClubUitslagen()
  {
    $widget_ops = array('classname' => 'MijnClubUitslagen', 'description' => 'Toont de uitslagen van alle teams in de periode die geselecteerd is' );
    $this->WP_Widget('MijnClubUitslagen', 'MijnClub Uitslagen', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	
	$devOptions = mijnclub_getOptions();
	
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT

	if (mijnclub_authenticate('widget-uitslagen')) {
		$atts = array('soort' => 'nee');
		echo mijnclub_printuitslagen($atts);
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}

	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubUitslagen");') );

class MijnClubStatistieken extends WP_Widget
{
  function MijnClubStatistieken()
  {
    $widget_ops = array('classname' => 'MijnClubStatistieken', 'description' => 'Toont de statistieken (Wedstrijden/Goals/Assists/Goals per wedstrijd/Assists per wedstrijd) van het geselecteerde team' );
    $this->WP_Widget('MijnClubStatistieken', 'MijnClub Statistieken', $widget_ops);
  }
 
  function form($instance)
  {
	$devOptions = mijnclub_getOptions();
	
	$defaults = array( 'title' => 'Topscoorders', 'topaantal' => '5', 'team' => $devOptions['eersteteam'], 'soort' => 'g' );
    $instance = wp_parse_args( (array) $instance, $defaults );
    $title = $instance['title'];
	$topaantal = $instance['topaantal'];
	$team = $instance['team'];
	$soort = $instance['soort'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
  <p><label for="<?php echo $this->get_field_id('topaantal'); ?>">Aantal: <input class="widefat" id="<?php echo $this->get_field_id('topaantal'); ?>" name="<?php echo $this->get_field_name('topaantal'); ?>" type="text" value="<?php echo attribute_escape($topaantal); ?>" /></label></p>
  <p><label for="<?php echo $this->get_field_id('team'); ?>">Team: 
  <?php
	$xmlurl = 'http://www.mijnclub.nu/clubs/teams/xml/'.$devOptions['clubcode'];
	$xml = mijnclub_loadxml($xmlurl);
	$chosen = $team;
	echo "<select name=\"".$this->get_field_name('team')."\" id=\"".$this->get_field_id('team')."\">\n";
	if ($chosen== '') {
		echo "<option value=\"\" SELECTED></option>\n"; //empty option
	}

	$cat = ""; //empty categorie at first team
	$first = true; //only true for first category
	foreach ($xml->team as $team) { //prints each team as an option
		$naam = $team->naam;
		$n_cat = (string) $team->soort;
		if ($n_cat != $cat) { //whenever a new category is found, an optgroup tag is inserted
			$cat = $n_cat;
			if (!$first) {
				echo "</optgroup>\n";
			}
			echo "<optgroup label=\"".$cat."\">\n";
			$first = false;
		}
		echo "<option value=\"".$naam."\" ".selected($chosen,$naam,false).">".$naam."</option>\n";
	}

	echo "</optgroup>\n</select>\n";
  ?>
  </label></p>
  <p><label for="<?php echo $this->get_field_id('soort'); ?>">Soort:
  <select id="<?php echo $this->get_field_id('soort'); ?>" name="<?php echo $this->get_field_name('soort'); ?>">
	<option value="w" <?php echo selected($soort,'w',false);?>>Gespeelde Wedstrijden</option>
	<option value="g" <?php echo selected($soort,'g',false);?>>Gemaakte Doelpunten</option>
	<option value="a"	<?php echo selected($soort,'a',false);?>>Gemaakte Assists</option>
	<option value="gw" <?php echo selected($soort,'gw',false);?>>Goals per Wedstrijd</option>
	<option value="aw" <?php echo selected($soort,'aw',false);?>>Assists per Wedstrijd</option>
  </select>
  </label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
	$instance['topaantal'] = $new_instance['topaantal'];
	$instance['team'] = $new_instance['team'];
	$instance['soort'] = $new_instance['soort'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	
	$devOptions = mijnclub_getOptions();
	
    if (!empty($title))
      echo $before_title . $title . $after_title;;

	//START WIDGET CONTENT

	if (mijnclub_authenticate('widget-statistieken')) {
		$aantal = $instance['topaantal'];
		$soort = $instance['soort'];
		$team = $instance['team'];
		mijnclub_topscoorders($team,$aantal,$soort);
	} else { //authentication failed
		echo 'Clubcode niet geauthoriseerd!';
	}

	//END WIDGET CONTENT
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("MijnClubStatistieken");') );

function mijnclub_maakteamarray() { 
	$teamarray = array();
	
	$teampageid = mijnclub_get_page('Teams')->ID;
	$args = array (
		'sort_order' => 'ASC',
		'sort_column' => 'menu_order',
		'hierarchical' => 0,
		'meta_key' => 'mijnclub',
		'meta_value' => 'true',
		'parent' => $teampageid,
		'post_type' => 'page',
		'post_status' => 'publish'
	);
	
	$cat = ''; //empty categorie at first team

	$catpages = get_pages($args);
	foreach ($catpages as $c) {
		$cat = (string) $c->post_title;
		if ($cat != 'Alle Teams') { //dont check "Alle teams"
			$args = array (
				'sort_order' => 'ASC',
				'sort_column' => 'menu_order',
				'hierarchical' => 0,
				'meta_key' => 'mijnclub',
				'meta_value' => 'true',
				'parent' => $c->ID,
				'post_type' => 'page',
				'post_status' => 'publish'
			);
			$teampages = get_pages($args);
			if ($teampages != false) { //makes sure there are no errors thrown when no pages are returned
				foreach($teampages as $t) {
					$naam = $t->post_title;
					$url = get_option('siteurl').'/?p='.$t->ID;
					$teamarray[$url] = $naam; //adds to array
				}
			}
		}
	}
	
	return $teamarray;
}

function mijnclub_topscoorders($teamname,$aantal,$stat) {
	$data = get_option("mijnclub_statistieken_".mijnclub_clean($teamname),array());
	switch ($stat) {
		case 'g':
			$statname = 'Goals';
		break;
		case 'w':
			$statname = 'Wedstr.';
		break;
		case 'a':
			$statname = 'Assists';
		break;
		case 'gw':
			$statname = 'G/W*';
		break;
		case 'aw':
			$statname = 'A/W*';
		break;
		
	}
	$team = $data;
	$value_arr = array();
	foreach ($team as $speler) {
		$value_arr[] = $speler[$stat];
	}
	arsort($value_arr);
	$keys = array_keys($value_arr);
	if (count($value_arr) > 0) {
		echo "<div class=\"mijnclub-statistieken\">\n";
		echo "<table class='widget-statistieken'>\n
				<thead>\n
					<tr>\n
						<td>#</td>\n
						<td>Naam</td>\n
						<td>$statname</td>\n
					</tr>\n
				</thead>\n";
		if ($stat == 'gw') {
			echo "<tfoot>\n
					<tr>\n
						<td colspan='3' class=\"small\">G/W*: Goals per Wedstrijd.</td>\n
					</tr>\n
				</tfoot>";
		} elseif($stat == 'aw') {
			echo "<tfoot>
					<tr>\n
						<td colspan='3' class=\"small\">A/W*: Assists per Wedstrijd.</td>\n
					</tr>\n
				</tfoot>\n";
		}
		echo "<tbody>\n";
		for ($i =0;$i < count($value_arr) && $i < $aantal;$i++) {
			$name = $team[$keys[$i]]['name'];
			if (in_array($stat,array('gw','aw'))) {
				$val = number_format($team[$keys[$i]][$stat],2);
			} else {
				$val = $team[$keys[$i]][$stat];
			}
			$rank = $i+1;
			echo "<tr>\n
					<td>$rank</td>\n
					<td>$name</td>\n
					<td>$val</td>\n
				</tr>\n";
		}
		echo "</tbody>\n</table>\n";
		echo '</div>';
	} else {
		echo "Geen statistieken gevonden voor het gekozen team.";
	}
}


?>