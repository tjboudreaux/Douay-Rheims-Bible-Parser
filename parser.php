<?php

require_once "db.php";

$new_section = "__________________________________________________________________";
$handle = @fopen("douayr.txt", "r");

$con = mysql_connect($host, $username, $password);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysql_select_db($db_name, $con);

if ($handle) {
    
    $current_book = null;
    $current_chapter = null;
    $current_verse = null;
    $current_text = null;
    
    while (($buffer = fgets($handle, 4096)) !== false) {
        echo $buffer;
        
        //check for book in line
        if (substr($buffer,0,3) == "The" || 
            substr_count(trim($buffer), "Solomons Canticle of Canticles") > 0 ||
            trim($buffer) == "Ecclesiastes"||
            trim($buffer) == "Ecclesiasticus"){

            $current_book = trim($buffer);
            $current_chapter = null;
            $current_verse = null;
            $current_text = null;
            //echo "Current Book: $current_book";
        }
        //check for chapter in line
        elseif (strstr($buffer,"Chapter")){
            $current_chapter = trim(str_replace("Chapter ","",$buffer));
            //echo "Current Chapter is $current_chapter\n";
        }
        //check for verse in line
        elseif(substr(ltrim($buffer),0,1) == "^"){
            $regex = "/\^[0-9]+/";
            $matches = array();
            preg_match($regex, $buffer, $matches);
            
            if (sizeof($matches) == 1){
                $current_verse = str_replace("^","", $matches[0]);
                $current_text = "";
                echo "$current_book $current_chapter:$current_verse \n";
            }
        }
        
        //get current text
        if ($current_book != null && $current_chapter != null && $current_verse != null){
            if ($buffer != $new_section and strlen(trim($buffer)) > 0 ) {
                $current_text .= $buffer;
            } else {
                $current_text = trim(str_replace("  ", " ", str_replace("\n", "", str_replace("^$current_verse","",$current_text))));
                echo "$current_text \n";
                
                $query = sprintf("INSERT INTO bible_verses (book, chapter, verse, text,language,translation) 
                                VALUES ('%s', %d, %d, '%s', '%s','%s')",
                                $current_book, $current_chapter, $current_verse, $current_text, 'en','douay');
                    
                mysql_query($query);
                
            }
        }
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);

    mysql_close($con);
    
}
?>