<?php

// Make sure to disable other filtypes uploading
$root = 'http://dominick.p.elu.so/fun/sm/';
$homeurl = 'http://dominick.p.elu.so/fun/sm/index.php';
$uploaddir = 'up/';

if(empty($_FILES)){
	$filename = $_GET['filename'];
} else {
	$allowedExts = array("sm", "dwi", "txt", "rtf");
	$extension = end(explode(".", $_FILES["file"]["name"]));
	if ((($_FILES["file"]["type"] == "text/plain")
	|| ($_FILES["file"]["type"] == "application/octet-stream"))
	&& ($_FILES["file"]["size"] < 40000)
	&& in_array($extension, $allowedExts)){
		if ($_FILES["file"]["error"] > 0){
			//echo "Error: " . $_FILES["file"]["error"] . "<br>";
			//print_r($_FILES);
			header('Location: '.$homeurl.'?alert=error');
		} else {
			// Success
			$filename = $_FILES['file']['name'];
			$uploadfile = $uploaddir . basename($filename);
			move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
			header('Location: '.$homeurl.'?filename='.$filename.'&alert=success');
		}
	} else {
		header('Location: '.$homeurl.'?alert=error');
		//print_r($_FILES);
	}
}
if(!empty($filename)){
	//print_r($_FILES);
	$chart = file_get_contents($uploaddir.$filename, true);
	$rows = explode(";", $chart);

	$ordered = array();
	$chartcount = 0;
	foreach($rows as $key => &$row){
		preg_match("/(#(.*?):)/", $row, $clean);
		// Clean title for key
		$title = $clean[2];
		$titlex = $clean[0];
		// Clean content for value
		$content = preg_replace("/$titlex/", '', $row);
		// Loop for NOTES (charts) and add them into a multidimensional array
		if($title == 'NOTES'){
			$chartcount++;
			// Break notes out into multi array
			$ordered['NOTES'][$chartcount] = explode(":", $content);
			
		} else {
			$ordered[$title] = $content;
		}
	}
	//array_shift($rows);
	// Break out BPMS into array with beat as key and bpm as value
	$ordered['BPMS'] = explode(',', $ordered['BPMS']);
	foreach($ordered['BPMS'] as $bpm){
		$bpmchange = explode('=', $bpm);
		$cleanbpm[trim($bpmchange[0])] = number_format(trim($bpmchange[1]), 0);
	}
	// Break out STOPS into array with beat as key and stop duration as value
	$ordered['STOPS'] = explode(',', $ordered['STOPS']);
	foreach($ordered['STOPS'] as $stop){
		$stopchange = explode('=', $stop);
		$cleanstop[trim($stopchange[0])] = trim($stopchange[1]);
	}

	// Put all of the song's basic info into 1 array
	$songinfo = array(
		title => $ordered['TITLE'],
		subtitle => $ordered['SUBTITLE'],
		artist => $ordered['ARTIST'],
		credit => $ordered['CREDIT'],
		music => $ordered['MUSIC'],
		selectable => $ordered['SELECTABLE'],
		background => $ordered['BACKGROUND'],
		banner => $ordered['BANNER'],
		cdtitle => $ordered['CDTITLE'],
		displaybpm => $ordered['DISPLAYBPM'],
		offset => $ordered['OFFSET'],
		samplestart => $ordered['SAMPLESTART'],
		samplelength => $ordered['SAMPLELENGTH'],
		bpms => $ordered['BPMS'],
		stops => $ordered['STOPS'],
	);

//print_r($songinfo);
//print_r($ordered);
//echo $ordered['NOTES'];
//print_r($ordered['NOTES']);

	foreach ($ordered['NOTES'] as &$notes){

		if (is_array($notes)){
			//  Scan through inner loop
			foreach ($notes as &$value) {
				$value = explode(",", trim($value));
				//echo '<pre>';
				//print_r($value);
				//echo '</pre>';
			}
			// Rename first 6 arrays
			$notes['NotesType'] = $notes[0];
			$notes['Description'] = $notes[1];
			$notes['DifficultyClass'] = $notes[2];
			$notes['DifficultyMeter'] = $notes[3];
			$notes['RadarValues'] = $notes[4];
			$notes['NoteData'] = $notes[5];
			// Remove old after rename
			unset($notes[0], $notes[1], $notes[2], $notes[3], $notes[4], $notes[5]);
			// Clean up NotesType
			preg_match("/(\b[\w-]+\b)/", $notes['NotesType'][0], $notes['NotesType'][0]);
			$NotesType = $notes['NotesType'][0][0];
			unset($notes['NotesType'][0]);
			$notes['NotesType'][0] = $NotesType;
			
			foreach ($notes['NoteData'] as $i => $value) {
			//foreach ($notes['NoteData'] as &$value) {
				$notes['NoteData'][$i] = explode("\n", trim($value));
				//$value = explode("\n", trim($value));
			}
			// Look for first lines starting with comment and delete if found
			foreach ($notes['NoteData'] as $i => $value) {
				//print_r($value);
				$notes['NoteData']['Measure'.$i] = $notes['NoteData'][$i];
				unset($notes['NoteData'][$i]);
				if (is_array($value)){
					foreach ($value as $k => $step) {
						// Delete any comment lines "// Measure 1"
						if (strpos($step,'//') !== false) {
							unset($notes['NoteData']['Measure'.$i][$k]);
							//print_r($notes['NoteData']['Measure'.$i][$k]);
							
						} else {
							// Break measures into step arrays
							$notes['NoteData']['Measure'.$i][$k] = str_split(trim($notes['NoteData']['Measure'.$i][$k]), 1);
						}
					}
				}
			}
			
		}else{
		}
	}
}
/*
foreach ($breakout as &$value){
	trim($value);
}*/

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php if(!empty($songinfo['title'])) echo $songinfo['title'].' - '; ?>Simfile Explorer</title>
	<style>
	</style>
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
	<link href="dropzone.css" rel="stylesheet">
	<link href="style.css" rel="stylesheet">
	<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
	<script src="dropzone.js"></script>
	<script>
		Dropzone.options.mydropzone = {
		dictDefaultMessage: "",
		maxFilesize: 1,
		  init: function() {
			this.on("success", function(file) { 
				//alert(file.name); 
				window.location.replace("index.php?filename="+file.name);
			});
		  }
		};
	</script>
</head>
<body>
	<div class="container">
	
	<?php
		if($_GET['alert'] == 'success'){
			echo '<br><div class="alert alert-success">'.$filename.' successfully uploaded. <a class="close" data-dismiss="alert" href="#">&times;</a></div>';
		}
		if($_GET['alert'] == 'error'){
			echo '<br><div class="alert alert-error">There was an error uploading your file. Maybe the wrong filetype or too big? <a class="close" data-dismiss="alert" href="#">&times;</a></div>';
		}
	?>
		<form class="dropzone" id="mydropzone" enctype="multipart/form-data" action="index.php" method="POST">
		<div class="row">
			<div class="span12" id="header">
				<h1>Simfile Explorer</h1>
				<p class="lead">This tool will give you general information about a stepmania chart file. Drag a .SM file in this box to get started.</p>
			</div>
		</div>
				<!-- The data encoding type, enctype, MUST be specified as below -->

		<!-- MAX_FILE_SIZE must precede the file input field -->
		<input type="hidden" name="MAX_FILE_SIZE" value="300000" />
		<!-- Name of input element determines name in $_FILES array -->

		</form>
		<div class="row">
			<div class="span8">
			<?php
				if(!empty($songinfo)){
					echo '<h3>'.$songinfo['title'].' by '.$songinfo['artist'].'</h3>';
						if(!empty($songinfo['subtitle'])) echo '<h5>'.$songinfo['subtitle'].'</h5>';
					echo '<table class="table table-bordered table-condensed">
								<tr>
									<th>Title</th>
									<th>Value</th>
								</tr>';
									if(!empty($songinfo['credit'])) echo '<tr><td>Creator</td><td>'.$songinfo['credit'].'</td></tr>';
									if(!empty($songinfo['music'])) echo '<tr><td>Song name</td><td>'.$songinfo['music'].'</td></tr>';
									if(!empty($songinfo['displaybpm'])) echo '<tr><td>Display BPM</td><td>'.$songinfo['displaybpm'].'</td></tr>';
									if(!empty($songinfo['stops'])) echo '<tr><td>Stops</td><td><pre>'; print_r($cleanstops); echo'</pre></td></tr>';
									echo '<tr><td>Step Classes</td><td>';
									foreach($ordered['NOTES'] as $value){
										if (is_array($value)){
											//  Scan through inner loop
											foreach ($value as $key => &$value) {
												if($key == 'DifficultyClass') echo '<a class="btn" data-toggle="collapse" data-target="#'.$value[0].'">'.$value[0].' ';
												if($key == 'DifficultyMeter') echo '('.$value[0].')</a> ';
											}
										}
									}
									echo '</td></tr>';
							echo '
							</table>
						<div class="input-prepend input-append">
								<span class="add-on">Share URL</span>
								<input class="input-xlarge" id="URL" type="text" placeholder="URL" value="'.$homeurl.'?filename='.$filename.'" >
								<a href="'.$root.$uploaddir.$filename.'" target="_blank" class="btn">Download</a>
						</div>
					';
					echo '<hr>';
					/*
					echo '<pre>';
					print_r($ordered['NOTES'][1]['NoteData']);
					echo '</pre>';
					*/
				} else {
				}
				
			?>
			</div>
			<div class="span4">
	<?php
		if(!empty($songinfo)){
			//<div id="'.$value['DifficultyClass'][0].'" class="collapse in"><h4>'.$value['DifficultyClass'][0].' Chart</h4>
			echo '<div class="accordion" id="charts">';
			$in = null;
			foreach($ordered['NOTES'] as $key => $value){
				if($key == count($ordered['NOTES'])) $in = 'in';
				echo '
					
					  <div class="accordion-group">
						<div class="accordion-heading">
						  <a class="accordion-toggle" data-toggle="collapse" data-parent="#charts" href="#'.$value['DifficultyClass'][0].'">
							'.$value['DifficultyClass'][0].'
						  </a>
						</div>
						<div id="'.$value['DifficultyClass'][0].'" class="accordion-body collapse '.$in.'">
							<div class="accordion-inner">
								<div class="stepcontainer">';
					if (is_array($value)){
						$holdingleft = 0; // Keep holding for freeze
						$holdingup = 0; // Keep holding for freeze
						$holdingdown = 0; // Keep holding for freeze
						$holdingright = 0; // Keep holding for freeze
						$rollingleft = 0; // Keep holding for roll
						$rollingup = 0; // Keep holding for roll
						$rollingdown = 0; // Keep holding for roll
						$rollingright = 0; // Keep holding for roll
						$redcount = 0;
						//  Scan for measures
						foreach ($value['NoteData'] as $key => $measure) {	
							$rows_num = count($measure); // Count how many rows exist in this measure
							$step_position = 0; // Set step position for counting
							
							echo '<div class="measure" id="'.$key.'"><span class="measurecount">M'.substr($key, 7).'</span>';
								if (is_array($measure)){
									foreach ($measure as $row) {
										
										if($step_position == 0) $step_position = $rows_num;
										$color = round($rows_num/$step_position, 3); // Get color code
										$step_position++;
										$notecolors = array(
											'red' => array(1, 0.8, 0.667, 0.571),
											'blue' => array(0.889, 0.727, 0.615, 0.533), 
											'green' => array(0.941, 0.842, 0.762, 0.696, 0.64, 0.593, 0.552, 0.516), 
											'purple' => array(	0.96, 0.923, 0.857, 0.828, 0.774, 0.75, 0.706, 0.686, 
																0.649, 0.632, 0.6, 0.585, 0.558, 0.545, 0.522, 0.511,
																0.98, 0.906, 0.873, 0.814, 0.787, 0.738, 0.716, 0.676, 
																0.658, 0.623, 0.608, 0.578, 0.565, 0.539, 0.527, 0.505), 
											'yellow' => array(	0.97, 0.914, 0.865, 0.821, 0.78, 0.744, 0.711, 0.681,
																0.653, 0.627, 0.604, 0.582, 0.561, 0.542, 0.525, 0.508), 
											'lightblue' => array(0.985, 0.955, 0.928, 0.901, 0.877, 0.853, 0.831, 0.81, 
																0.79, 0.771, 0.753, 0.736, 0.719, 0.703, 0.688, 0.674, 
																0.66, 0.646, 0.634, 0.621, 0.61, 0.598, 0.587, 0.577, 
																0.566, 0.557, 0.547, 0.538, 0.529, 0.52, 0.512, 0.504), 
											
										);
										foreach ($notecolors as $col => $value){
											foreach ($value as $val){
												if($color == $val)	$color = $col;
											}	
										};
										
										
										
										// Find color codes not set in array above
										//if(!is_string($color)) echo $color.', ';
										
										echo '<div class="steprow N'.$rows_num.' '.$color.' clearfix">';
										// Grab red row count
										if($color == 'red'){
											
											echo '<span class="beatcount">'.$redcount.'</span>';
											foreach($cleanbpm as $beat => $bpm){

												if($redcount == $beat){
													echo '<span class="bpmchange">'.$bpm.' bpm</span>';
												} else if($beat > $redcount && $beat < ($redcount + 1)){
													$beatpos = explode('.', $beat, 2);
													echo '<span class="bpmchange P'.($beatpos[1]).'" >'.$bpm.' bpm</span>';
												}
											}
											$redcount++;
										} else {
										}
										
										if (is_array($measure)){
											foreach ($row as $direction => $arrow) {
												if($direction == 0) $direction = 'L';
												if($direction == 1) $direction = 'D';
												if($direction == 2) $direction = 'U';
												if($direction == 3) $direction = 'R';
												echo '<div class="arrowwrap N'.$rows_num.' "><span class="arrow N'.$rows_num.' '.$direction.$arrow.' '.$color.' P'.$step_position.'"></span>';
												// Start the holding variables for freezes if the freeze arrow and direction appears
												if($arrow == 2 && $direction == 'L'){
													$holdingleft = 1;
												} else if($arrow == 2 && $direction == 'D'){
													$holdingdown = 1;
												} else if($arrow == 2 && $direction == 'U'){
													$holdingup = 1;
												} else if($arrow == 2 && $direction == 'R'){
													$holdingright = 1;
												}
												// Start the rolling variables for rolls if the roll arrow and direction appears
												if($arrow == 4 && $direction == 'L'){
													$rollingleft = 1;
												} else if($arrow == 4 && $direction == 'D'){
													$rollingdown = 1;
												} else if($arrow == 4 && $direction == 'U'){
													$rollingup = 1;
												} else if($arrow == 4 && $direction == 'R'){
													$rollingright = 1;
												}
												// Clear freezes if the end of the freeze with a matching direction shows up
												if($arrow == 3 && $direction == 'L'){
													$holdingleft = 0;
													$rollingleft = 0;
												} else if($arrow == 3 && $direction == 'D'){
													$holdingdown = 0;
													$rollingdown = 0;
												} else if($arrow == 3 && $direction == 'U'){
													$holdingup = 0;
													$rollingup = 0;
												} else if($arrow == 3 && $direction == 'R'){
													$holdingright = 0;
													$rollingright = 0;
												}
												/*
												// Clear rolls if the end of the roll with a matching direction shows up
												if($arrow == 3 && $direction == 'L'){
													$rollingleft = 0;
												} else if($arrow == 3 && $direction == 'D'){
													$rollingdown = 0;
												} else if($arrow == 3 && $direction == 'U'){
													$rollingup = 0;
												} else if($arrow == 3 && $direction == 'R'){
													$rollingright = 0;
												}
												*/
												echo '</div>';
												/*
												if($direction.$arrow == $holdfor) $holdfor = 0;
												if($holdfor !== 0 && $direction.$arrow !== $holdfor){
													echo '<span class="hold"></span>';
												}
												*/
											}
										}
										if($holdingleft == 1) echo '<span class="hold left"></span>';
										if($holdingup == 1) echo '<span class="hold up"></span>';
										if($holdingdown == 1) echo '<span class="hold down"></span>';
										if($holdingright == 1) echo '<span class="hold right"></span>';	
										if($rollingleft == 1) echo '<span class="roll left"></span>';
										if($rollingup == 1) echo '<span class="roll up"></span>';
										if($rollingdown == 1) echo '<span class="roll down"></span>';
										if($rollingright == 1) echo '<span class="roll right"></span>';
										echo '</div>';
									}
									
								}
							echo'</div>';
						}
					}
				echo'	<div class="clearfix"> </div></div>
				  
							</div>
						</div>
					</div>';
			}
			echo '</div>';
		}
	?>
				

			</div>
		</div>
	</div>

</body>
</html> 