<?php

echo "<div class=\"icon32\" id=\"icon-users\"><br></div><h2> MijnClub Team Statistieken </h2>\n";
$devOptions = mijnclub_getOptions();
$xmlurl = 'http://www.mijnclub.nu/clubs/teams/xml/'.$devOptions['clubcode'];
$xmlstring = mijnclub_getdata($xmlurl);
$teamsxml = simplexml_load_string($xmlstring);

$data = array();
$content = array();
$aantalteams = 0;

if (isset($_POST['updateTeamEnable'])) {
	foreach ($teamsxml->team as $team) {
		$naam = (string)$team->naam;
		$t = mijnclub_clean($naam);
		if (isset($_POST["statistieken_enabled_$t"])) {
			$enabled[$t] = 'yes';
		} else {
			$enabled[$t] = 'no';
		}
	}
	update_option('mijnclub_statistieken_enabled',$enabled);
}

if (isset($_POST['updateGegevens'])) {
	foreach ($teamsxml->team as $team) {
		$naam = (string)$team->naam;
		$url = 'http://www.mijnclub.nu/clubs/teams/embed/'.$devOptions['clubcode'].'/team/'.$naam;
		$htmlstring = mijnclub_getdata($url);
		
		//replacing &raquo;/&laquo; to prevent warnings being displayed when trying to read in strings with raquo/laquo in them.
		$htmlstring = str_replace('&raquo;', '&#187;', $htmlstring); 
		$htmlstring = str_replace('&laquo;', '&#171;', $htmlstring);

		$xml = simplexml_load_string($htmlstring);

		$spelers = $xml->xpath('//*[@id="spelers"]');
		if (!empty($spelers)) {
			$spelerxml = $spelers[0]->asXML();
			$spelers = htmlentities(str_replace(array('<p id="spelers">','</p>'),'',$spelerxml),ENT_COMPAT ,'UTF-8');
			$spelers_arr = explode(', ',$spelers);
			$heeftspelers = true;
		} else {
			$spelers_arr = array();
			$heeftspelers = false;
		}
		
		$data[$naam] = array();

		$t = mijnclub_clean($naam);
		$i = 0;

		foreach($spelers_arr as $s) {
			$data[$naam][$i] = array();
			$sc = mijnclub_clean_name(mijnclub_clean($s));

			$w = "$t-$sc-wedstrijden";
			$g = "$t-$sc-goals";
			$a = "$t-$sc-assists";
			$gw = "$t-$sc-goals-wedstrijd";
			$aw = "$t-$sc-assists-wedstrijd";
			$w_val = isset($_POST[$w])?(int)$_POST[$w]:0;
			$g_val = isset($_POST[$g])?(int)$_POST[$g]:0;
			$a_val = isset($_POST[$a])?(int)$_POST[$a]:0;
			
			$gw_val = ($w_val == 0)?0:$g_val/$w_val;
			$aw_val = ($w_val == 0)?0:$a_val/$w_val;
			$gw_val_format = number_format($gw_val,2);
			$aw_val_format = number_format($aw_val,2);

			$data[$naam][$i]['name'] = $s;
			$data[$naam][$i]['w'] = $w_val;
			$data[$naam][$i]['g'] = $g_val;
			$data[$naam][$i]['a'] = $a_val;
			$data[$naam][$i]['gw'] = $gw_val;
			$data[$naam][$i]['aw'] = $aw_val;
			
			$i++;
		}
		if ($heeftspelers) {
			update_option("mijnclub_statistieken_$t",$data[$naam]);
		}
	}
}

foreach ($teamsxml->team as $team) {
	ob_start();
	$naam = (string)$team->naam;
	
	$url = 'http://www.mijnclub.nu/clubs/teams/embed/'.$devOptions['clubcode'].'/team/'.$naam;
	$htmlstring = mijnclub_getdata($url);
	
	//replacing &raquo;/&laquo; to prevent warnings being displayed when trying to read in strings with raquo/laquo in them.
	$htmlstring = str_replace('&raquo;', '&#187;', $htmlstring); 
	$htmlstring = str_replace('&laquo;', '&#171;', $htmlstring);

	$xml = simplexml_load_string($htmlstring);

	$spelers = $xml->xpath('//*[@id="spelers"]');
	if (!empty($spelers)) {
		$spelerxml = $spelers[0]->asXML();
		$spelers = htmlentities(str_replace(array('<p id="spelers">','</p>'),'',$spelerxml),ENT_COMPAT,'UTF-8');
		$spelers_arr = explode(', ',$spelers);
		$heeftspelers = true;
	} else {
		$spelers_arr = array();
		$heeftspelers = false;
	}
	
	$t = mijnclub_clean($naam);
	$i = 0;
	
	$saved = get_option("mijnclub_statistieken_$t",array());
	echo "<h1>$naam</h1>\n";
	if ($heeftspelers) {
		echo "<table class='statistieken'>\n
			<thead>\n
				<tr>\n
					<td>Naam</td>\n
					<td class='center'>Wedst.</td>\n
					<td class='center'>Goals</td>\n
					<td class='center'>Assists</td>\n
					<td class='leftdivider center'>G/W*</td>\n
					<td class='center'>A/W*</td>\n
				</tr>\n
			</thead>\n
			<tfoot>\n
				<tr><td colspan='6'><small>* G/W: Goals per Wedstrijd, A/W: Assists per Wedstrijd.</small></td></tr>\n
				<tr><td colspan='6'><small>Deze velden worden automatisch berekend, en kunnen niet aangepast worden.</small></td></tr>\n
			</tfoot>\n";
		echo "<tbody>\n";
	} else {
		echo '<p>Er zijn geen spelers gevonden</p>\n';
	}
	foreach($spelers_arr as $s) {
		$sc = mijnclub_clean_name(mijnclub_clean($s));

		$w = "$t-$sc-wedstrijden";
		$g = "$t-$sc-goals";
		$a = "$t-$sc-assists";
		$gw = "$t-$sc-goals-wedstrijd";
		$aw = "$t-$sc-assists-wedstrijd";
		$w_val = isset($_POST[$w])?(int)$_POST[$w]:0;
		$g_val = isset($_POST[$g])?(int)$_POST[$g]:0;
		$a_val = isset($_POST[$a])?(int)$_POST[$a]:0;
		
		$w_val = (int) isset($saved[$i]['w'])? $saved[$i]['w']:0;
		$g_val = (int) isset($saved[$i]['g'])? $saved[$i]['g']:0;
		$a_val = (int) isset($saved[$i]['a'])? $saved[$i]['a']:0;
		
		$gw_val = ($w_val == 0)?0:$g_val/$w_val;
		$aw_val = ($w_val == 0)?0:$a_val/$w_val;
		$gw_val_format = number_format($gw_val,2);
		$aw_val_format = number_format($aw_val,2);

		echo "<tr>\n
				<td class='spelernaam'>$s</td>\n
				<td>\n
					<input id=\"$w\" name=\"$w\" type=\"text\" value=\"$w_val\" />\n
				</td>\n
				<td>\n
					<input id=\"$g\" name=\"$g\" type=\"text\" value=\"$g_val\" />\n
				</td>\n
				<td class='rightdivider'>\n
					<input id=\"$a\" name=\"$a\" type=\"text\" value=\"$a_val\" />\n
				</td>\n
				<td class='leftdivider'>\n
					<input class='readonly' id=\"$gw\" name=\"$gw\" type=\"text\" value=\"$gw_val_format\" readonly='' />\n
				</td>\n
				<td>\n
					<input class='readonly' id=\"$aw\" name=\"$aw\" type=\"text\" value=\"$aw_val_format\" readonly='' />\n
				</td>\n
			</tr>\n";
			
		$i++;
	}
	if ($heeftspelers) {
		echo "</tbody>\n</table>\n";
	}
	
	$c = ob_get_clean();
	$content[$aantalteams] = $c;
	$namen[$aantalteams] = $naam;
	$heeftspelers_arr[$aantalteams] = $heeftspelers;
	$aantalteams++;
}

$enabled = get_option('mijnclub_statistieken_enabled',array());
$activated = 0;
for ($i =0; $i < $aantalteams; $i++) {
	$t = mijnclub_clean($namen[$i]);
	if ($heeftspelers_arr[$i]&&isset($enabled[$t])&&$enabled[$t]=='yes') {
		$activated++;
	}
}
echo "<form method=\"post\" action=\"\">\n";

if ($activated>0) {
	echo "<input id=\"submit\" class=\"button-primary\" type='submit' name=\"updateGegevens\" value=\"Sla statistieken op\"/>\n";
	//prints tabs
	echo "<div id=\"tabs\" class=\"statistieken\">\n
						<ul>\n";
	for ($i =0; $i < $aantalteams; $i++) {
		$t = mijnclub_clean($namen[$i]);
		if ($heeftspelers_arr[$i]&&$enabled[$t]=='yes') {
			echo "<li><a class=\"tab-link\" href=\"#tabs-$i\">".$namen[$i]."</a></li>\n";
		}
	}
	echo "</ul>\n";
	for ($i =0; $i < $aantalteams; $i++) {
		$t = mijnclub_clean($namen[$i]);
		if ($heeftspelers_arr[$i]&&$enabled[$t]=='yes') {
			echo "<div id=\"tabs-$i\">".$content[$i]."</div>\n";
		}
	}
	echo "</div>\n";

	echo "<input id=\"submit\" class=\"button-primary\" type='submit' name=\"updateGegevens\" value=\"Sla statistieken op\"/>\n";
} else {
	echo "<p>\n
			Er zijn geen teams geactiveerd, of er zijn geen teams met spelers.<br />\n
			Klik hieronder om teams te activeren, of ga naar <a href=\"http://www.mijnclub.nu/teams\">Mijnclub.nu</a> om spelers toe te voegen.\n
		</p>\n";
}
echo "<div class='teams-statistieken-keuze'>\n";
echo "<div class='statistieken_enabled' id='accordion'>\n";
echo "<h3>Vink hier de teams aan waarvoor je de statistieken bij wilt houden (Klik om te openen)</h3>\n";
echo "<div>\n";
$cat = ""; //empty categorie at first team
foreach ($teamsxml->team as $team) { //prints each team as an option
	$naam = (string) $team->naam;
	$soort = (string) $team->soort;
	if ($cat != $soort) { //whenever a new category is found, an optgroup tag is inserted
		$cat = $soort;
		echo "<div class='clear'></div>\n";
		echo "<h4>$cat</h4>\n";
	}
	$t = mijnclub_clean($naam);
	$checked = (isset($enabled[$t])&&$enabled[$t]=='yes')?"checked=''":'';
	
	echo "<div class='statistieken_inner'>\n";
	echo "<input type='checkbox' name='statistieken_enabled_$t' value='yes' $checked/>\n";
	echo "<label for='statistieken_enabled_$t'>$naam</label>\n";
	
	echo "</div>\n"; 
}

echo "</div>\n</div>\n";
echo "<div class='clear'></div>\n";
echo "<br />\n";
echo "<input id=\"submit\" class=\"button-primary\" type='submit' name=\"updateTeamEnable\" value=\"Sla team-wijzigingen op\"/>\n";
echo "</div>\n</form>\n";



?>