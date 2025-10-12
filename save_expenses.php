<?php
$file = 'expenses.txt';

if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($lines)) {
        echo '<table border="1">';
        echo '<tr><th>Date</th><th>Garden Area</th><th>Item</th><th>Amount (£)</th><th>Supplier</th></tr>';
        
        $total = 0;
        foreach ($lines as $line) {
            // Each line is expected in the format: date | area | item | £amount | supplier
            $parts = explode('|', $line);
            if (count($parts) === 5) {
                echo '<tr>';
                foreach ($parts as $part) {
                    echo '<td>' . htmlspecialchars(trim($part)) . '</td>';
                }

                // Extract the amount value for the total
                $amountStr = trim($parts[3]); // e.g., "£12.50"
                $amountVal = floatval(str_replace('£', '', $amountStr));
                $total += $amountVal;

                echo '</tr>';
            }
        }

        echo '<tr><td colspan="3"><strong>Total</strong></td><td colspan="2"><strong>£' . number_format($total, 2) . '</strong></td></tr>';
        echo '</table>';
    } else {
        echo '<p>No expense entries found.</p>';
    }
} else {
    echo '<p>Expense log file not found.</p>';
}
?>
