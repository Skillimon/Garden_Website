<?php
// Simple expenses manager: supports categories PLANT, LABOUR, BUILD, PERSONAL
// Data format (pipe-separated): category|date|area|description|amount|supplier|hours|rate|comment

$file = 'expenses.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = isset($_POST['category']) ? substr(trim($_POST['category']), 0, 30) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $area = isset($_POST['area']) ? trim($_POST['area']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
    $supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : '';
    $hours = isset($_POST['hours']) ? trim($_POST['hours']) : '';
    $rate = isset($_POST['rate']) ? trim($_POST['rate']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Normalise numbers
    $amount = $amount === '' ? '' : number_format((float)$amount, 2, '.', '');
    $rate = $rate === '' ? '' : number_format((float)$rate, 2, '.', '');
    $hours = $hours === '' ? '' : number_format((float)$hours, 2, '.', '');

    $line = implode('|', array($category, $date, $area, $description, $amount, $supplier, $hours, $rate, $comment));
    file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);

    header('Location: expenses.php');
    exit();
}

// CSV download
if (isset($_GET['download'])) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="expenses.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, array('Category','Date','Area','Description','Amount','Supplier','Hours','Rate','Comment'));
  if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $ln) {
      $parts = explode('|', $ln);
      $parts = array_pad($parts, 9, '');
      fputcsv($out, $parts);
    }
  }
  fclose($out);
  exit();
}

function format_amount($a) {
    return ($a === '' ? '' : '£' . number_format((float)$a, 2));
}

function h($s) { return htmlspecialchars($s); }

// Read entries
$entries = array();
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $ln) {
        $parts = explode('|', $ln);
        // pad to 9 elements
        $parts = array_pad($parts, 9, '');
        $entries[] = $parts;
    }
}

// Totals
$moneyTotal = 0.0;
$hoursTotal = 0.0;
foreach ($entries as $e) {
    $amt = (float)$e[4];
    $moneyTotal += $amt;
    $hrs = (float)$e[6];
    $hoursTotal += $hrs;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expenses - Montrose Garden</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .form-group{margin-bottom:16px}
    .form-group label{display:block;margin-bottom:6px;color:var(--muted);font-weight:600}
    .small{font-size:0.9em;color:var(--muted)}
    .totals-card{background:var(--card);padding:20px;border-radius:var(--radius);box-shadow:0 4px 12px rgba(16,48,72,0.06);margin-bottom:20px}
    .totals-card h3{margin-top:0;color:var(--accent)}
    .stat{display:inline-block;margin-right:30px}
    .stat-value{font-size:1.5rem;font-weight:700;color:var(--accent)}
  </style>
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header>
      <div class="container">
        <div class="brand">
          <a class="site-title" href="index.html">Montrose Garden</a>
          <p class="site-tag">Garden renovation & plant records</p>
        </div>
        <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
          <span class="menu-icon">☰</span> Menu
        </button>
        <nav>
          <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="expenses.php">Expenses</a></li>
            <li><a href="summary.php">Summary</a></li>
            <li><a href="container_plants.html">Container Plants</a></li>
            <li><a href="weeds.html">Weeds</a></li>
          <li><a href="invasive_plants.html">Invasive Plants</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main id="main-content" class="container">
      <h1>Garden Expenses</h1>

      <div class="totals-card">
        <h3>Current Totals</h3>
        <div class="stat">
          <div class="stat-value"><?php echo '£' . number_format($moneyTotal, 2); ?></div>
          <div class="small">Total Spend</div>
        </div>
        <div class="stat">
          <div class="stat-value"><?php echo number_format($hoursTotal, 2); ?></div>
          <div class="small">Total Hours</div>
        </div>
        <a href="expenses.php?download=1" class="btn" style="margin-left:20px">Download CSV</a>
      </div>

      <form method="post" action="expenses.php" id="expenseForm" class="card">
        <h2 style="margin-top:0;color:var(--accent)">Add New Entry</h2>
        
        <div class="form-group">
          <label for="category">Category:</label>
          <select id="category" name="category">
            <option value="PLANT">Plant purchase</option>
            <option value="LABOUR">External labour (gardener)</option>
            <option value="BUILD">Building / contractor</option>
            <option value="PERSONAL">Personal time</option>
          </select>
        </div>

        <div class="form-group">
          <label for="date">Date:</label>
          <input type="date" id="date" name="date" required>
        </div>

        <div class="form-group">
          <label for="area">Garden area / Location:</label>
          <input id="area" name="area" placeholder="e.g. Main Border or Where planted">
        </div>

        <div class="form-group">
          <label for="description">Item / Description:</label>
          <input id="description" name="description" placeholder="e.g. Acer, Fence work, Weeding">
        </div>

        <div class="form-group">
          <label for="amount">Amount (£):</label>
          <input type="number" step="0.01" id="amount" name="amount">
        </div>

        <div class="form-group">
          <label for="supplier">Supplier / Contractor:</label>
          <input id="supplier" name="supplier">
        </div>

        <div class="form-group">
          <label for="hours">Hours (for labour/time):</label>
          <input type="number" step="0.1" id="hours" name="hours">
        </div>

        <div class="form-group">
          <label for="rate">Rate (£/h):</label>
          <input type="number" step="0.01" id="rate" name="rate">
        </div>

        <div class="form-group">
          <label for="comment">Comment:</label>
          <input id="comment" name="comment" maxlength="100">
        </div>

        <input type="submit" value="Save Entry" class="btn">
      </form>

    <h2 style="color:var(--accent);margin-top:40px">Saved Entries</h2>
    <div class="table-responsive">
      <table>
    <thead>
      <tr>
        <th>Category</th>
        <th>Date</th>
        <th>Area</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Supplier</th>
        <th>Hours</th>
        <th>Rate</th>
        <th>Comment</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $e): ?>
        <tr>
          <td><?php echo h($e[0]); ?></td>
          <td><?php echo h($e[1]); ?></td>
          <td><?php echo h($e[2]); ?></td>
          <td><?php echo h($e[3]); ?></td>
          <td><?php echo h(format_amount($e[4])); ?></td>
          <td><?php echo h($e[5]); ?></td>
          <td><?php echo h($e[6]); ?></td>
          <td><?php echo h($e[7] === '' ? '' : '£' . number_format((float)$e[7],2)); ?></td>
          <td><?php echo h($e[8]); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
    </div>
  </main>

  <script>
    // Simple helper: when category changes, adjust placeholders / required fields
    const catEl = document.getElementById('category');
    const amountEl = document.getElementById('amount');
    const hoursEl = document.getElementById('hours');
    const rateEl = document.getElementById('rate');

    function adjustFields() {
      const c = catEl.value;
      if (c === 'PLANT') {
        amountEl.required = true; hoursEl.required = false; rateEl.required = false;
        amountEl.placeholder = 'Cost of plant';
      } else if (c === 'LABOUR') {
        hoursEl.required = true; rateEl.required = true; amountEl.required = false;
        amountEl.placeholder = 'Optional total (auto-calc if left blank)';
      } else if (c === 'BUILD') {
        amountEl.required = true; hoursEl.required = false; rateEl.required = false;
      } else if (c === 'PERSONAL') {
        hoursEl.required = true; rateEl.required = false; amountEl.required = false;
      }
    }

    catEl.addEventListener('change', adjustFields);
    adjustFields();

    // When LABOUR is submitted, if amount is blank calculate from hours * rate
    document.getElementById('expenseForm').addEventListener('submit', function(e) {
      if (catEl.value === 'LABOUR') {
        const hrs = parseFloat(hoursEl.value || '0');
        const r = parseFloat(rateEl.value || '0');
        const a = parseFloat(amountEl.value || '');
        if ((!a || a === 0) && hrs && r) {
          amountEl.value = (hrs * r).toFixed(2);
        }
      }
    });

    // Mobile menu toggle
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('nav ul');
    
    if (toggleBtn && navMenu) {
      toggleBtn.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
        navMenu.classList.toggle('show');
      });
    }
  </script>
</body>
</html>
