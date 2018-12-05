<?php
// Allow php to get variabls from previous instance
session_start();

// Connect to the database
$mysqli = mysqli_connect("localhost", "root", "", "AdventureGame");
// Initialize canmove variable
$canmove = True;

// Get the previous position, but if there isnt one set x and y to zero
if (isset($_SESSION['x']) & isset($_SESSION['y'])) {
    $x = $_SESSION['x'];
    $y = $_SESSION['y'];
    $ox = $_SESSION['x'];
    $oy = $_SESSION['y'];
} else {
    $x = 0;
    $y = 0;
}

// Change the position based on the command if it is set
if (isset($_GET['command'])) {
    $command = explode("/", $_GET['command']);

    if ($command[0] == "pickup") {
        $itemID = (int)$command[1];
        if (is_int($itemID)) {
            $mysqli->query("UPDATE Items SET carried=1 WHERE id=$itemID");
        }

    } else if ($command[0] == "kill") {
        $enemyID = (int)$command[1];
        $mysqli->query("DELETE FROM `enemies` WHERE `enemies`.`ID` = $enemyID");
    } else if ($command[0] == "drop") {
        $itemID = (int)$command[1];
        $roomID = (int)$command[2];
        if (is_int($itemID) && is_int($roomID)) {
            $mysqli->query("UPDATE Items SET carried=0 WHERE id=$itemID");
            $mysqli->query("UPDATE Items SET rID=$roomID WHERE id=$itemID");
        }
    } else {
        $result = $mysqli->query("SELECT * FROM Locations WHERE y = $y AND x = $x");
        $row = $result->fetch_assoc();
        $roomID = $row['ID'];
        if ($command[0] == "north") {
            $y += 1;
        } if ($command[0] == "east") {
            $x += 1;
        } if ($command[0] == "south") {
            $y -= 1;
        } if ($command[0] == "west") {
            $x -= 1;
        }

        // If the next location is empty then set canmove to false and move the player back
        $result = $mysqli->query("SELECT * FROM Locations WHERE y = $y AND x = $x");
        $row = $result->fetch_assoc();
        if ($row['Location'] == "") {
            $canmove = False;
            $x = $ox;
            $y = $oy;
        }
        $roomID = $row['ID'];
        $mysqli->query("INSERT INTO `enemies` (`ID`, `Name`, `rID`) VALUES (NULL, 'pichoochoo', '".$roomID."')");

        // Set the session position to the current position
        $_SESSION['x'] = $x;
        $_SESSION['y'] = $y;
    }

}

// Query the database and get the Location and Description
$result = $mysqli->query("SELECT * FROM Locations WHERE y = $y AND x = $x");
$row = $result->fetch_assoc();
$roomID = $row['ID'];
$location = $row['Location'];
$description = $row['Description'];

$item = $mysqli->query("SELECT * FROM Items");
$roomMessage = "";
$carryMessage = "";
$carryingAnItem = 0;

while ($itemRow = $item->fetch_assoc()) {
    $itemName = $itemRow['Name'];
    $itemDescription = $itemRow['Description'];
    $itemRoom = $itemRow['rID'];
    $itemCarried = $itemRow['carried'];
    if ($itemCarried == 1) {
        $carryMessage = $carryMessage."<br>You are carrying a ".$itemName.'! It is '.$itemDescription.'.';
        $carryingAnItem = 1;
    }
    if ($itemRoom == $roomID && $itemCarried == 0) {
        $roomMessage = $roomMessage.'<br>There is a '.$itemName.'! It is '.$itemDescription.'.';
    }
}

$result = $mysqli->query("SELECT * FROM Enemies WHERE rID = $roomID");
$enemyMessage = "";
while ($enemy = $result->fetch_assoc()){
    $enemyMessage = $enemyMessage.$enemy['Name']." ".$enemy['ID']." is here!<br>";
}

?>

<!DOCTYPE html>
<html>
    <!-- Set title, icon and stylesheet for the page. -->
    <head>
        <title>Adventure Game!</title>
        <meta charset="UTF-8">
        <link rel="icon" href="favicon.png">
        <!-- <link rel="stylesheet" type="text/css" href="stylesheet.css"> -->
    </head>

    <!-- Main body -->
    <body align=left>

        <!-- Create title and setup div for location and description. Php inside div to display the relevant information -->
        <h1 title="memes">Adventure Game!</h1>
        <div id="location" style="position: relative; width:750px;height:75px; float: left;">
            <?php echo "<h1>".$location." - Current Position: (".$x.",".$y.")</h1>"; ?>
        </div>
        <div id="description" style="width:750px;height:400px;float: left; font-size: 30px;">
            <?php
            if ($canmove == False) { echo 'You cannot go that way.<br><br>';} echo "<h3 style='display: inline;''>Description<br></h3>".$description."<br>";
            if ($enemyMessage != "") { echo $enemyMessage;}
            if ($roomMessage != "") { echo $roomMessage;}
            if ($carryMessage != "") { echo $carryMessage;}
            ?>
        </div>

        <!-- Form for submitting the command which you want to travel in to the php server -->
        <h1 style="display: inline;">
            <form id=commandform action="/AdventureGame/prototype.php" method="get">
                <label><input type=radio name='command' value='north'>North</input><br></label>
                <label><input type=radio name='command' value='east'>East</input><br></label>
                <label><input type=radio name='command' value='south'>South</input><br></label>
                <label><input type=radio name='command' value='west'>West</input><br></label>
                <?php
                $item = $mysqli->query("SELECT * FROM Items");
                while ($itemRow = $item->fetch_assoc()) {
                    $itemName = $itemRow['Name'];
                    $itemCarried = $itemRow['carried'];
                    $itemID = $itemRow['ID'];
                    $itemRoom = $itemRow['rID'];
                    if ($itemRoom == $roomID && $itemCarried == 0) {
                        echo "<label><input type=radio name='command' value='pickup/".$itemID."'>Pickup ".$itemName."</input><br></label>";
                    }
                    if ($itemCarried == 1) {
                        echo "<label><input type=radio name='command' value='drop/".$itemID."/".$roomID."'>Drop ".$itemName."</input><br></label>";
                    }
                }

                if ($carryingAnItem == 1) {
                    $result = $mysqli->query("SELECT * FROM Enemies WHERE rID = $roomID");
                    while ($enemy = $result->fetch_assoc()) {
                        echo "<label><input type=radio name='command' value='kill/".$enemy["ID"]."'>Kill ".$enemy['Name']." ".$enemy['ID']."</input><br></label>";
                    }
                }
                ?>
                <input style="height:50px; width:100px" type=submit text="Submit">
            </form>
        </h1>

    </body>
</html>
