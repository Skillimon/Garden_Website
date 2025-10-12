<?php
$filename = "activities.txt";

if (file_exists($filename)) {
    $file = fopen($filename, "r");

    echo "<table border='1'>";
    echo "<tr><th>Date</th><th>Garden Area</th><th>Activity</th><th>Hours Spent</th></tr>";

    while (($line = fgets($file)) !== false) {
        $fields = explode(",", trim($line));
        if (count($fields) === 4) {
            echo "<tr>";
            foreach ($fields as $field) {
                echo "<td>" . htmlspecialchars($field) . "</td>";
            }
            echo "</tr>";
        }
    }

    echo "</table>";
    fclose($file);
} else {
    echo "<p>No activity logs yet.</p>";
}
?>
