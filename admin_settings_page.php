<?php 
	
	global $adminOptionsName;
	$devOptions = mijnclub_getOptions();
	$clubcodechange = false;
	$aantalwedstrijdenchange = false;
	$showpoweredchange = false;
	$eersteteamchange = false;
	$showauthenticate = false;
	$eersteteamnaamchange = false;
	
	wp_register_style('mijnclubstyle', get_bloginfo('wpurl').'/wp-content/plugins/mijnclub/css/mijnclub.css');
	wp_enqueue_style('mijnclubstyle');

	//checks which setting is changed, changes setting, and takes note of this in variable
	if (isset($_POST['update_mijnclubSettings'])) {
		if (isset($_POST['clubcode'])) {
			if ($devOptions['clubcode'] != $_POST['clubcode']) { $clubcodechange = true;}
			$devOptions['clubcode'] = wp_strip_all_tags($_POST['clubcode']);
		}
		if (isset($_POST['aantalwedstrijden'])) {
			if ($devOptions['aantalwedstrijden'] != $_POST['aantalwedstrijden']) { $aantalwedstrijdenchange = true;}
			$devOptions['aantalwedstrijden'] = wp_strip_all_tags($_POST['aantalwedstrijden']);
		}
		if (isset($_POST['showpowered'])) {
			if ($devOptions['showpowered'] != $_POST['showpowered']) { $showpoweredchange = true;}
			$devOptions['showpowered'] = wp_strip_all_tags($_POST['showpowered']);
		}
		if (isset($_POST['eersteteam'])) {
			if ($devOptions['eersteteam'] != $_POST['eersteteam']) { $eersteteamchange = true;}
			$devOptions['eersteteam'] = wp_strip_all_tags($_POST['eersteteam']);
		}
		if (isset($_POST['eersteteamnaam'])) {
			if ($devOptions['eersteteamnaam'] != $_POST['eersteteamnaam']) { $eersteteamnaamchange = true;}
			$devOptions['eersteteamnaam'] = wp_strip_all_tags($_POST['eersteteamnaam']);
		}
		//saves options to database
		update_option($adminOptionsName,$devOptions);
		
		if ($aantalwedstrijdenchange == true || $showpoweredchange == true || $eersteteamchange == true || $eersteteamnaamchange == true ) {
			echo "<div class=\"updated\"><p><strong>Instellingen ge&#252;pdate.</strong></p></div>";
		}
	}

	if (mijnclub_authenticate('admin')) {
		if ($clubcodechange == true) {
			echo '<div class="updated"><p><strong>Succesvol geauthenticeerd,  U kunt nu de plugin gebruiken</strong></p></div>';
		}
		//checks if refreshbutton is pressed and does the refreshing if it was
		if (isset($_POST['refreshpaginas'])) {
			mijnclub_refreshpages();
		}
		//clears cache if button is pressed
		if (isset($_POST['clearxmlcache'])) {
			mijnclub_clearcache();
			echo '<div class="updated"><p><strong>XML Cache is geleegd.</strong></p></div>';
		}
		//adds mijnclub pages to selected menu
		if (isset($_POST['addtomenu'])){
			mijnclub_refreshpages();
			$menu = wp_strip_all_tags($_POST['menu']);
			$status = mijnclub_add_pages_to_menu($menu);
			$menuobj = get_term($menu,'nav_menu');
			if ($status[0]) {
				echo "<div class=\"updated\"><p><strong>Alle wedstrijdpagina's zijn toegevoegd aan '".$menuobj->name."'</strong></p></div>";
			} 
			if ($status[1]) {
				echo "<div class=\"updated\"><p><strong>Alle teampagina's zijn toegevoegd aan '".$menuobj->name."'</strong></p></div>";
			}
			if (!$status[0]&&!$status[1]) {
				echo "<div class=\"updated\"><p><strong>Er is niks toegevoegd aan '".$menuobj->name."'</strong></p></div>";
			}
		}
		
		//options form
		echo "<div class=\"icon32\" id=\"icon-options-general\"><br></div><h2> MijnClub Plugin Opties </h2>\n";
		echo "<form method=\"post\" action=\"\">\n";
		echo "<table class=\"form-table\">\n
		<tbody>\n
		<tr valign=\"top\">\n
		<th scope=\"row\">\n
		<label for=\"clubcode\">Clubcode</label>\n
		</th>\n
		<td>\n
		<input id=\"clubcode\" type='text' name='clubcode' value=\"".$devOptions['clubcode']."\"/>\n
		</td>\n
		</tr>\n
		<tr valign=\"top\">\n
		
		<th scope=\"row\">\n
		<label for=\"aantalwedstrijden\">Widget: Aantal wedstrijden tonen</label>\n
		</th>\n
		<td>\n
		<select name=\"aantalwedstrijden\">\n";
		$chosen = $devOptions['aantalwedstrijden'];
		for ($i=2;$i<=10;$i++) {
			echo "<option value=\"".$i."\" ".selected($i,$chosen,false).">".$i."</option>\n";
		}
		echo "</select>\n";
		echo "</td>\n
		</tr>\n";
		
		echo "<tr valign=\"top\">\n
		<th scope=\"row\">\n
		<label for=\"eersteteamnaam\">Widget: Naam eerste team*</label>\n
		</th>\n
		<td>\n
		<input id=\"eersteteamnaam\" type='text' name='eersteteamnaam' value=\"".$devOptions['eersteteamnaam']."\"/>\n
		</td>\n
		</tr>\n";
		
		echo "<tr valign=\"top\">\n
		<th scope=\"row\">\n
		<label for=\"eersteteam\">Widget: Eerste team keuze*</label>\n
		</th>\n
		<td>\n";
		$chosen = $devOptions['eersteteam'];
	
		$xmlurl = 'http://www.mijnclub.nu/clubs/teams/xml/'.$devOptions['clubcode'];
		$xml = mijnclub_loadxml($xmlurl);
		
		echo "<select name=\"eersteteam\">\n";
		if ($chosen== '') {
			echo "<option value=\"\" SELECTED></option>\n"; //empty option
		}
		
		$cat = ""; //empty categorie at first team
		$first = true; //only true for first category
		foreach ($xml->team as $team) { //prints each team as an option
			$naam = $team->naam;
			$soort = (string) $team->soort;
			if ($cat != $soort) { //whenever a new category is found, an optgroup tag is inserted
				$cat = $soort;
				if (!$first) {
					echo "</optgroup>\n";
				}
				echo "<optgroup label=\"".$cat."\">\n";
				$first = false;
			}
			echo "<option value=\"".$naam."\" ".selected($chosen,$naam,false).">".$naam."</option>\n";
		}
		
		echo "</optgroup>\n</select>\n";
		echo "</td>\n
		</tr>\n";
		
		echo "<tr valign=\"top\">\n
		<th scope=\"row\">\n
		<label for=\"showpowered\">Laat 'powered by mijnclub.nu' zien</label>\n
		</th>\n
		<td>\n";
		if ($devOptions['showpowered']=='true') {
			echo "<input type='radio' name='showpowered' value='true' checked /> Ja\n
			<input type='radio' name='showpowered' value='false' /> Nee\n";
		} else {
			echo "<input type='radio' name='showpowered' value='true' /> Ja\n
			<input type='radio' name='showpowered' value='false' checked/> Nee\n";
		}
		echo "</td>\n
		</tr>\n
		</tbody>\n
		</table>\n";
		
		//submit button
		echo "<div class=\"mnclbsubmit\">
		<input id=\"submit\" class=\"button-primary\" type='submit' name=\"update_mijnclubSettings\" value=\"Update Instellingen\"/></div>
		</form>";
		
		//end options form
		
		//menu dropdown
		$mijnclubmenu = (int) wp_get_nav_menu_object('MijnClub Menu')->term_id;
		$menus = get_terms('nav_menu',array('hide_empty'    => false, 'exclude' => array($mijnclubmenu)));
		
		if (count($menus)) {
			echo "<form method=\"POST\" action=\"\">\n";
			echo "<input type=\"submit\" name=\"addtomenu\" class=\"button-primary\" value=\"Voeg mijnclubpagina's aan menu toe\">\n
			<select name=\"menu\">\n";
			foreach($menus as $menu){
				echo "<option value=\"".$menu->term_id."\">".$menu->name."</option>\n";
			}
			echo "</select>
			Pagina's zullen ook ververst worden.\n
			</form>";
		}
		
		//clear refresh pages button
		echo "<form method=\"POST\" action=\"\">\n
		<div class=\"mnclbsubmit\">
		<input id=\"submit\" class=\"button-primary\" type=\"submit\" name=\"refreshpaginas\" value=\"Ververs Teampagina's\"/></div>\n
		</form>";
		
		//refresh xml cache button
		echo "<form method=\"POST\" action=\"\">\n 
		<div>\n
		<div class=\"mnclbsubmit\">\n
		<input id=\"submit\" class=\"button-primary\" type=\"submit\" name=\"clearxmlcache\" value=\"Clear XML-Cache\"/><div>\n
		</div></form>\n
		<div class=\"clear\"></div>
		<p>Door op de <strong>\"Ververs Teampagina's\"</strong> knop te drukken worden alle teampagina's en teammenu's ververst\n
		<br>Het verversen van alle pagina's kan even duren, bij witte pagina gewoon wachten</p>
		<p>Door op de <strong>\"Clear XML-Cache\"</strong> knop te druken worden alle gecachede MijnClub XML files verwijderd.\n
		<br>Dit kan nodig zijn als je de teampaginas wilt updaten en niet 2uur wilt wachten (dan verloopt de cache)</p>\n
		<p><strong>*</strong> Kies het eerste team, en vul de naam is zoals hij is weergegeven in de Stand van Mijnclub.nu (bijv. KSV 1)</p>";	
		
	} else { //athentication failed (or is first time authenticating)
		if ($devOptions['clubcode']=='') {
			echo '<div class="updated"><p><strong>Authenticatie gefaald! Voer een clubcode in</strong></p></div>';
		} else {
			echo '<div class="updated"><p><strong>Authenticatie gefaald! Er is geen juiste clubcode ingevoerd of U heeft geen toegang tot de plugin</strong></p></div>';
			echo '<div class="updated"><p><strong>Neem contact op met <a href="mailto:info@nh-websites.nl?Subject=MijnClub%20Plugin%20toegang">info@nh-websites.nl</a> als u nog geen toegang heeft tot de plugin</strong></p></div>';
		}
		
		echo "<div class=\"icon32\" id=\"icon-options-general\"><br></div><h2> MijnClub Plugin Opties </h2>\n";
		//new clubcode optie
		echo "<form method=\"post\" action=\"\">\n";
		echo "<table class=\"form-table\">\n
		<tbody><tr valign=\"top\"><th scope=\"row\"><label for=\"clubcode\">Clubcode</label></th>\n
		<td>\n
		<input id=\"clubcode\" type='text' name='clubcode' value=\"".$devOptions['clubcode']."\"/>\n
		</td>\n
		</tr>\n
		<tr valign=\"top\">\n
		</tbody>\n
		</table>\n";

		echo "<div class=\"mnclbsubmit\">
		<input id=\"submit\" class=\"button-primary\" type='submit' name=\"update_mijnclubSettings\" value=\"Authenticeer\"/></div>
		</form>\n
		<p>Voer uw clubcode in en druk op Authenticeer om de plugin te authenticeren en te gebruiken</p>";
	}
?>