<?php
    //echo "Note: If multiple errors occur on around line 70, clear cookies and refresh page";
    /*
        GROUP CONTRIBUTIONS
            Kyle Clem: 5 hours - UI design -- layout, 
            Danny Mendoza: 5 hours
            Ethan Milner: 5 hours - Database design, Character and word input into database
            Jacob Novak: 5 hours - Session/game setup, game logic, win lose conditions

            AWS: http://hangman2-env.eba-mngsfc32.us-east-1.elasticbeanstalk.com/
    */
    //connection string -- handled in func too but also for ethan code
    $conn = mysqli_connect("project-db.cz20saucocg3.us-east-1.rds.amazonaws.com", "admin", "fBropq%k&C%3VvqjQ9vQ", "HANGMAN_GAME");


    function queryFunc($SQLParam)
    {
      
        $conn = mysqli_connect("project-db.cz20saucocg3.us-east-1.rds.amazonaws.com", "admin", "fBropq%k&C%3VvqjQ9vQ", "HANGMAN_GAME");
            //check if valid
        
        if(!$result = mysqli_query($conn,$SQLParam))
        {
            die("Error with Query: ". mysqli_connect_error(). ": ". mysqli_connect_error());
        }
        mysqli_close($conn);
        return $result;
    }




    /* 
    4/21 code SESSION SETUP -- keep track of session using unique session id
    */

    //variables that are retrieved from games table in DB
    $game_id;   //used to store the game_id
    $session_id;    //stores the current game session
    $word;    //store the word that will be used for handman game
    $numIncorrect_guesses;  //used to determine which image to display and if game is lost
    $score;
    $printArray;   //array to hold what gets printed
    $gameCondition = 3;     //0 == lose, 1 == win, 3==ongoing


    //use to start and resume the current session
    session_start();


    //query last row in games to see if port num matches
    $SQLString = "SELECT GAME_ID, GAME_STATE FROM GAMES ORDER BY GAME_ID;";


    //create an associate array by querying database using query function
    $row = mysqli_fetch_assoc(queryFunc($SQLString));

   

    // Check if the user has an existing session
    //set up website variables for this game of hangman
    //create row in games table for this game
    if (!isset($_SESSION['user_id'])) 
    {

        // Generate a unique user ID from PHP session to store in db
        $_SESSION['user_id'] = uniqid();
        

        //get random word from db
        $SQLString = "SELECT * FROM WORDS WHERE WORD_ID ORDER BY RAND() LIMIT 1;";

        //create an associate array by querying database using query function
        $row = mysqli_fetch_assoc(queryFunc($SQLString));

        //store rand as the id or word
        $word_id = $row["WORD_ID"];


        //query to create new entry in GAMES
        $SQLString = "INSERT INTO GAMES (SESSION_ID, WORD_ID, INCORRECT_GUESSES, GAME_STATE, WIN_CONDITION) VALUES ('" . $_SESSION['user_id'] . "'," . $word_id . ",1,'ONGOING',3);";
        
        queryFunc($SQLString);
    }
    


    //THIS SECTION IS TO SET THE GAME VARIABLES TO KEEP TRACK
    $SQLString = "SELECT * FROM GAMES WHERE SESSION_ID = '" . $_SESSION['user_id'] . "';";

    $result = mysqli_fetch_assoc(queryFunc($SQLString));

    $game_id = $result["GAME_ID"];

    $session_id = $result["SESSION_ID"];
    
    $numIncorrect_guesses = $result["INCORRECT_GUESSES"];

    $gameCondition = $result["WIN_CONDITION"];


    //query to get word associated with game and port
    $SQLString = "SELECT WORD FROM WORDS INNER JOIN GAMES USING (WORD_ID) WHERE GAMES.GAME_ID = " . $game_id . ";";
    $result = mysqli_fetch_assoc(queryFunc($SQLString));
    $word = $result["WORD"];//store the word that will be used for handman game

    //echo "<h1>";
    //echo "GAME: " . $game_id . "\tSES_ID: " . $session_id . "\tWORD: " . $word . "\tLIVES: " . $numIncorrect_guesses;
    
    //string of _ to show how manny chars there are in the word
    $printArray = str_repeat("_", strlen($word));

    //echo "</h1>";
?>

<?php 
    /*
        4/21 
        win condition
        0 = lose
        1 = win
        3 = keep playing
        if 0 or 1, do not display the game html
    */

    //loose
    if($gameCondition == 0)
    {
        echo "<center>";
        echo "<h1>YOU LOST</h1>";
        echo "<h1> Word was: " . $word . "</h1>";
        echo "<h1>Click button or refresh page to play again</h1>";
        
        //update game status to finished
        $SQLString = "UPDATE GAMES SET GAME_STATE='FINISHED' WHERE GAME_ID = " . $game_id . ";";
        queryFunc($SQLString);

        //reset button
        ?>
        <form><button type="submit" action = "index.php">Play Again </button> </form>
        
        <?php
        //reset session variables to play a new game
        session_unset();
    }
    else if($gameCondition == 1)
    {
        echo "<center>";
        echo "<h1>YOU WON</h1>";
        echo "<h1> Word was: " . $word . "</h1>";
        echo "<h1>Click button or refresh page to play again</h1>";
        //reset button
        


        $SQLString = "UPDATE GAMES SET GAME_STATE='FINISHED' WHERE GAME_ID = " . $game_id . ";";
        queryFunc($SQLString);

        //reset button
        ?>
        <form><button type="submit" action = "index.php">Play Again </button> </form>
        <?php
        //reset session variables to play a new game
        session_unset();
    }
    else
    {
?>


<?php

$conn = mysqli_connect("project-db.cz20saucocg3.us-east-1.rds.amazonaws.com", "admin", "fBropq%k&C%3VvqjQ9vQ", "HANGMAN_GAME");

/* code to get char input -- validate whethere it has already been used and submit to guesses table in db*/
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    if (isset($_POST["letter"])) 
    {
        // If a letter guess is submitted
        $letter = $_POST["letter"];

        // Check if the same combination of GAME_ID and letter already exists
        $check_query = "SELECT * FROM GUESSES WHERE GAME_ID = " . $game_id ."  AND LETTER = '$letter'";
        $check_result = mysqli_query($conn, $check_query);

        
        if (mysqli_num_rows($check_result) > 0) 
        {
            // If the combination already exists, display a message or handle as needed
            echo "This guess already exists.";
        }
        else
        {
            // Insert the letter and game_id into the database table
            $insert_query = "INSERT INTO GUESSES (LETTER, GAME_ID) VALUES ('$letter', " . $game_id . ")";
            if (mysqli_query($conn, $insert_query)) {
            } else {
                echo "Error: " . $insert_query . "<br>" . mysqli_error($conn);
            }


            /*
            4/21 --  INCREMENT GUESSES  if not in word
            */
            $letter = strtoupper($letter);
            if(strpos($word,$letter) === false)
            {
                $numIncorrect_guesses = $numIncorrect_guesses + 1;
                //update database

                $SQLString = "UPDATE GAMES SET INCORRECT_GUESSES = " . $numIncorrect_guesses . " WHERE SESSION_ID = '" . $session_id . "';";

                //update table
                queryFunc($SQLString);
            }
        }
    }

    if (isset($_POST["submit_word_guess"])) 
    {
        // If a word guess is submitted
        $guessed_word = strtoupper($_POST["guessed_word"]);

        // Insert the guessed word and game_id into the database table
        $insert_query = "INSERT INTO GUESSES (GUESSED_WORD, GAME_ID) VALUES ('$guessed_word', " . $game_id . ")";
        if (mysqli_query($conn, $insert_query)) {
            echo "Word '$guessed_word' inserted successfully!";
        } else {
            echo "Error: " . $insert_query . "<br>" . mysqli_error($conn);
        }


        //NOT TESTED AT ALL
        //win condition
        if($guessed_word === $word)
        {
            //query db
            $SQLString = "UPDATE GAMES SET WIN_CONDITION = 1 WHERE SESSION_ID = '" . $session_id . "';";
            queryFunc($SQLString);

            //force refresh of page --http head
            header("Refresh:0");
        }
        
        else
        {
            //incremened guesses which is used for displauying images and lives
            $numIncorrect_guesses = $numIncorrect_guesses + 1;
            //update database

            $SQLString = "UPDATE GAMES SET INCORRECT_GUESSES = " . $numIncorrect_guesses . " WHERE SESSION_ID = '" . $session_id . "';";

            //update table
            queryFunc($SQLString);
        }
    }

    // Close the database connection
    mysqli_close($conn);
}
?>

<?php
    /*
    REALLY REALLY IMPORTANT CODE
    lose condition if guessess exceeded
    */
    if($numIncorrect_guesses > 7)
    {
        //query db
        $SQLString = "UPDATE GAMES SET WIN_CONDITION = 0 WHERE SESSION_ID = '" . $session_id . "';";
        queryFunc($SQLString);

        //set table instance to finished
        //force refresh of page 
        header("Refresh:0");
        
    }

?>


<!DOCTYPE html>
<html>
   <head>
      <title>Hangman Game</title>
      <link rel="stylesheet" href="styles.css" />
   </head>
   <body style="background-color: #b6e0d5">
      <div class="container">
         <img src="images/Logo.png" style="width: 25%; height: auto; display: block; margin: 0 auto" />
      </div>

      <div id="game" class="game-area">
         <div id="hangman"></div>
         <div id="word"></div>
         <div id="lives"></div>
         <div id="letters"></div>

        <?php
            /*
                Code to print out letters guessed form guessed table, if wrong, display to wrong text area, if right, put into string
            */

            //read from guess table with same session and print out guesess
            $SQLString = "SELECT GUESSES.LETTER FROM GUESSES INNER JOIN GAMES USING (GAME_ID)  WHERE GUESSES.GAME_ID = GAMES.GAME_ID AND GAMES.SESSION_ID = '" . $session_id . "';";
            $result = queryFunc($SQLString);

            //wrap in header
            echo "<h1>Guessed Letters</h1><h1>";

            //loop over guesses
            while ($row = mysqli_fetch_assoc($result))
            {
                //if not in string, add to list of guessed letters
                //=== strict compariosn
                if((strpos($word,$row["LETTER"])) !== false)
                {
                    for($i = 0; $i < strlen($word); $i++)
                    {
                        //=== strict compariosn
                        if($row["LETTER"] === $word[$i])
                        {
                            $printArray[$i] = $row["LETTER"];
                        }
                    }
                }
                else
                {
                    echo "<span>\t" . $row["LETTER"] . "</span>";
                }	
            }
            echo "</hr><br><h1>";
            //print loop
            for($i = 0; $i < strlen($printArray); $i++)
            {
                echo "\t " . $printArray[$i] . "\t";
            }
            echo "</h1>";

            //win condition -- if char guesses are right
            if($printArray === $word)
            {
                //query db
                $SQLString = "UPDATE GAMES SET WIN_CONDITION = 1 WHERE SESSION_ID = '" . $session_id . "';";
                queryFunc($SQLString);
                //force refresh of page 
                header("Refresh:0");
            }

            //get the correct image.
            //query images table with current lives remaining amount
            $SQLString = "SELECT IMAGE_URL FROM IMAGES INNER JOIN GAMES on GAMES.INCORRECT_GUESSES = IMAGES.IMAGE_ID WHERE SESSION_ID = '" . $session_id . "';";

            $row = mysqli_fetch_assoc(queryFunc($SQLString));

            $image = $row["IMAGE_URL"];
        
            echo "<img src=\"" . $image . "\" alt=\"" . $image . "\" style=\"border: 4px solid #9c725b; width: 40%; height: auto; display: block; margin: 0 auto\" />";
         
        ?>
         <div class="guess-container">
            <p class="guess-label">Enter the full word if you think you know it !!!</p>
                <!-- Form for word guess -->
                <form method="POST" action="index.php">
                    <input type="text" name="guessed_word" placeholder="Enter your word guess" maxlength="15" />
                    <button type="submit" name="submit_word_guess">Submit Word Guess</button>
                </form>
         </div>
         <div class="guess-container">
         <p class="guess-label">Select a letter:</p>
            <!-- Form for letter guesses -->
            <form method="POST" action="index.php">
               <div id="keyboard">
                  <!-- Buttons to select letters -->
                  <?php
                  // Generate buttons for each letter
                  for ($i = 65; $i <= 90; $i++) {
                      $letter = chr($i);
                      echo '<button type="submit" name="letter" value="' . $letter . '">' . $letter . '</button>';
                  }
                  ?>
               </div>
            </form>   
            <?php 
                /*
                    code getting user input
                */
                $conn = mysqli_connect("project-db.cz20saucocg3.us-east-1.rds.amazonaws.com", "admin", "fBropq%k&C%3VvqjQ9vQ", "HANGMAN_GAME");

                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["letter"])) {
                    $letter = $_POST["letter"];

                    // Check if the same combination of GAME_ID and letter already exists
                    $check_query = "SELECT * FROM GUESSES WHERE GAME_ID = " . $game_id . " AND letter = '$letter'";
                    $check_result = mysqli_query($conn, $check_query);

                    if (mysqli_num_rows($check_result) > 0) {
                        // If the combination already exists, display a message or handle as needed
                    } else {
                        // Insert the letter and game_id into the database table
                        $insert_query = "INSERT INTO GUESSES (LETTER, GAME_ID) VALUES ('$letter', " . $game_id . ")";
                        if (mysqli_query($conn, $insert_query)) {
                            echo "Letter '$letter' inserted successfully!";
                        } else {
                            echo "Error: " . $insert_query . "<br>" . mysqli_error($conn);
                        }
                    }

                // Close the database connection
                mysqli_close($conn);
                }
            ?>     
        </div>

      <div id="result"></div>
      <button id="play-again" style="display: none" onclick="playAgain()">Play Again</button>

      <div id="instructions" class="instructions">
         <!--<img src="images/Instructions.png" style="border: 2px solid #9c725b; width: 500px; height: auto; display: block; margin: 0 auto" /> -->
         <ul>
            <li>Guess the letters to reveal the hidden word !!!</li>
            <li>Each incorrect guess brings you closer to losing !!!</li>
            <li>Dont let the hangman be fully drawn !!!</li>
         </ul>
      </div>
      <script src="game.js"></script>
   </body>


<?php
    //closing brace from condition 0
    }
?>
</html>

