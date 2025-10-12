<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $area = $_POST['area'];
    $activity = $_POST['activity'];
    $hours = $_POST['hours'];

    $entry = "$date,$area,$activity,$hours\n";

    // Append to the file
    file_put_contents('activities.txt', $entry, FILE_APPEND | LOCK_EX);
    
    // Redirect back to form page
    header("Location: activity-log.html");
    exit();
}
?>
