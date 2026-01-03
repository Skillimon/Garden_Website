<?php
// Monthly summary: spend (plants / labour) and hours by source (gardener / me)
$file = 'expenses.txt';

$entries = [];
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $ln) {
        $parts = explode('|', $ln);
        $parts = array_pad($parts, 9, '');
        $entries[] = $parts; // [category,date,area,desc,amount,supplier,hours,rate,comment]
    }
}

$months = [];
$plants = [];
$labour = [];
$hours_by_source = []; // ['Gardener' => ['YYYY-MM' => hours], 'Me' => [...]]

foreach ($entries as $e) {
    $cat = strtoupper(trim($e[0]));
    $date = trim($e[1]);
    if ($date === '') continue;
    $m = date('Y-m', strtotime($date));
    $months[$m] = true;

    $amount = (float)$e[4];
    $supplier = trim($e[5]);
    $hours = (float)$e[6];
    $rate = (float)$e[7];

    if ($cat === 'PLANT') {
        if (!isset($plants[$m])) $plants[$m] = 0.0;
        $plants[$m] += $amount;
    }

    if ($cat === 'LABOUR') {
        // amount may be empty: calculate from hours * rate
        $amt = $amount;
        if ($amt <= 0 && $hours > 0 && $rate > 0) $amt = $hours * $rate;
        if (!isset($labour[$m])) $labour[$m] = 0.0;
        $labour[$m] += $amt;

        // classify source: gardener if supplier contains 'gard', else 'Contractor'
        $source = 'Contractor';
        if (stripos($supplier, 'gard') !== false) $source = 'Gardener';
        if ($source === 'Contractor' && strtolower($supplier) === 'me') $source = 'Me';

        if (!isset($hours_by_source[$source])) $hours_by_source[$source] = [];
        if (!isset($hours_by_source[$source][$m])) $hours_by_source[$source][$m] = 0.0;
        $hours_by_source[$source][$m] += $hours;
    }

    if ($cat === 'PERSONAL') {
        if (!isset($hours_by_source['Me'])) $hours_by_source['Me'] = [];
        if (!isset($hours_by_source['Me'][$m])) $hours_by_source['Me'][$m] = 0.0;
        $hours_by_source['Me'][$m] += $hours;
    }
}

// Create sorted list of months from earliest to latest
if (empty($months)) {
    $labels = [];
} else {
    $keys = array_keys($months);
    sort($keys);
    // expand months to fill gaps between first and last
    $start = strtotime($keys[0] . '-01');
    $end = strtotime(end($keys) . '-01');
    $labels = [];
    for ($t = $start; $t <= $end; $t = strtotime('+1 month', $t)) {
        $labels[] = date('Y-m', $t);
    }
}

// Prepare datasets
$plantsData = [];
$labourData = [];
$gardenerHours = [];
$meHours = [];

foreach ($labels as $m) {
    $plantsData[] = isset($plants[$m]) ? round($plants[$m],2) : 0;
    $labourData[] = isset($labour[$m]) ? round($labour[$m],2) : 0;
    $gardenerHours[] = isset($hours_by_source['Gardener'][$m]) ? round($hours_by_source['Gardener'][$m],2) : 0;
    $meHours[] = isset($hours_by_source['Me'][$m]) ? round($hours_by_source['Me'][$m],2) : 0;
}

function h($s){ return htmlspecialchars($s); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monthly Summary - Montrose Garden</title>
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-wrap canvas{max-height:400px}
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
    <h1>Monthly Summary</h1>

    <?php if (empty($labels)): ?>
      <div class="card">
        <p>No data yet. Add some expenses first.</p>
      </div>
    <?php else: ?>
      <h2 style="color:var(--accent);margin-top:30px">Spend by Month (Plants / Labour)</h2>
      <div class="two-col">
        <div>
          <div class="table-responsive">
            <table>
          <thead><tr><th>Month</th><th>Plants (£)</th><th>Labour (£)</th><th>Total (£)</th></tr></thead>
          <tbody>
          <?php foreach ($labels as $i=>$m): ?>
            <tr>
              <td><?php echo h($m); ?></td>
              <td><?php echo number_format($plantsData[$i],2); ?></td>
              <td><?php echo number_format($labourData[$i],2); ?></td>
              <td><?php echo number_format($plantsData[$i] + $labourData[$i],2); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          </table>
          </div>
      </div>
      <div class="chart-wrap">
        <canvas id="spendChart"></canvas>
      </div>
    </div>

    <h2 style="color:var(--accent);margin-top:40px">Hours by Month (Gardener / Me)</h2>
    <div class="two-col">
      <div>
          <div class="table-responsive">
            <table>
          <thead><tr><th>Month</th><th>Gardener (h)</th><th>My hours (h)</th><th>Total (h)</th></tr></thead>
          <tbody>
          <?php foreach ($labels as $i=>$m): ?>
            <tr>
              <td><?php echo h($m); ?></td>
              <td><?php echo number_format($gardenerHours[$i],2); ?></td>
              <td><?php echo number_format($meHours[$i],2); ?></td>
              <td><?php echo number_format($gardenerHours[$i] + $meHours[$i],2); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          </table>
          </div>
      </div>
      <div class="chart-wrap">
        <canvas id="hoursChart"></canvas>
      </div>
    </div>

    <script>
      const labels = <?php echo json_encode($labels); ?>;
      const plants = <?php echo json_encode($plantsData); ?>;
      const labour = <?php echo json_encode($labourData); ?>;
      const gardener = <?php echo json_encode($gardenerHours); ?>;
      const me = <?php echo json_encode($meHours); ?>;

      const ctx1 = document.getElementById('spendChart');
      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            { label: 'Plants (£)', data: plants, backgroundColor: 'rgba(75,192,192,0.6)' },
            { label: 'Labour (£)', data: labour, backgroundColor: 'rgba(192,75,192,0.6)' }
          ]
        },
        options: { responsive:true, scales: { y: { beginAtZero:true } } }
      });

      const ctx2 = document.getElementById('hoursChart');
      new Chart(ctx2, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: 'Gardener (h)', data: gardener, borderColor: 'rgb(54,162,235)', tension:0.2, fill:false },
            { label: 'Me (h)', data: me, borderColor: 'rgb(255,99,132)', tension:0.2, fill:false }
          ]
        },

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
        options: { responsive:true, scales: { y: { beginAtZero:true } } }
      });
      </script>
    <?php endif; ?>
  </main>

</body>
</html>
