<?php
	
	/*
	* Include connection file to connect to database
	*/
	
	include 'connection.php';
	
	/*
	* Basic Functions to be used
	*/
	
	function clean($str) {
	
		$str = str_replace(":","",$str);
		return $str;
		
	}
	
	function clean_br($str) {
	
		$str = str_replace("<br />","",$str);
		return $str;
	
	}
	
	//This function is only used for the DS page, to keep code consistent
	function clean_irregularities($str) {
	
		$str = str_replace("'","",$str);
		$str = str_replace(";","",$str);
		$str = str_replace("`","",$str);
		return $str;
	
	}
	
	function replace_special($str){
		$chunked = str_split($str,1);
		$str = ""; 
		foreach($chunked as $chunk) {
			$num = ord($chunk);
			// Remove non-ascii & non html characters
			if ($num >= 32 && $num <= 123){
					$str.=$chunk;
			}
		}   
		return $str;
	}
	
	function insert_db($topic, $link, $subtopic, $code) {
	
		global $con;
		$query = "INSERT INTO data_structures_code (Topic, Subtopic, Link, Code) VALUES ('$topic', '$subtopic', '$link', '$code');";
		//Table name is algorithms for Algorithms
		mysql_query($query, $con) or die("Could not Insert ".mysql_error());
	
	}
	
	/*
	* Input the seed URL
	*/
	
	$html = file_get_contents('http://www.geeksforgeeks.org/data-structures/'); 
	//http://www.geeksforgeeks.org/fundamentals-of-algorithms/ for the algorithms page

	/*
	*First find the main links section
	*/

	$find_string_start = '<div class="page-content">';
	$find_string_stop = '<div id="post-13067">'; //'Quizzes' for Algorithms;

	$start_index = strpos($html, $find_string_start);
	$stop_index = strpos($html, $find_string_stop, $start_index);

	$section_length = $stop_index - $start_index;
	$main_section = replace_special(substr($html, $start_index, $section_length));
	//$main_section = clean_irregularities($main_section); //This function is only used for the DS page.
	
	echo $main_section;

	/*
	* Now that we have all links
	* iterate over them, and visit
	* each link
	*/
	
	while(strpos($main_section, '</p>') !== false) {
		
		//First, we'll get the <strong> tags, to mark our subtopic in the database
		
		$subtopic_left = strpos($main_section, '<strong>') + 8; //To compensate for length of <strong>
		$subtopic_right = strpos($main_section, '</strong>'); //To get rid of colons and spaces
		
		$subtopic = clean(substr($main_section, $subtopic_left, $subtopic_right - $subtopic_left));		
		
		//Next, we find critical section, i.e only topic names and links
		
		$starting_position = strpos($main_section, '<a');
		$ending_position = strpos($main_section, '</p>', $subtopic_right);
		
		$critical_section = clean_br(substr($main_section, $starting_position, $ending_position - $starting_position));
		
		/*
		* Parse through all links in the critical section
		* To get the anchor string
		*/
		
		while(strpos($critical_section, '<a') !== false) {
		
			$anchor_start = strpos($critical_section, '<a');
			$anchor_end = strpos($critical_section, '</a>');
			
			$anchor = substr($critical_section, $anchor_start, $anchor_end - $anchor_start);
			
			//Now that we have anchor string, break up into topic and link
			
			$link_start = strpos($anchor, 'href="') + 6;
			$link_end = strpos($anchor, '"', $link_start + 1);
			
			$link = substr($anchor, $link_start, $link_end - $link_start);
			
			$topic_start = strpos($anchor, '>') + 1;
			
			$topic = substr($anchor, $topic_start);
			
			/*
			* Now, find the relevant section of html in given code
			*/
			
			$html_link = file_get_contents($link);
			$html_link_start = strpos($html_link, '<div class="post"');
			$html_link_end = strpos($html_link, '<script', $html_link_start);
			
			$code = clean_irregularities(substr($html_link, $html_link_start, $html_link_end - $html_link_start));
			
			//Now that we have topic, link, and code insert to db
			
			insert_db($topic, $link, $subtopic, $code);
			
			//Finally, reduce critical section to continue while loop
			
			$critical_section = substr($critical_section, $anchor_end + 4);
		
		}
				
		//Now, reduce the size of the main section
		
		$main_section = substr($main_section, $ending_position + 4); //To make up for the length of </p> tag
		
	}
	

?>
