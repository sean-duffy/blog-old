<?php
$input_error = false;

// Function to calculate square of value - mean
function sd_square($x, $mean) { return pow($x - $mean,2); }

// Function to calculate standard deviation (uses sd_square)    
function sd($array) {
    
// square root of sum of squares devided by N-1
return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
}

if (isset($_POST['getdata-submit'])) {

  if (!empty($_POST['stock_1']) && !empty($_POST['stock_2']) && !empty($_POST['stock_3']) && !empty($_POST['stock_4'])
    && !empty($_POST['allocation_1']) && !empty($_POST['allocation_2']) && !empty($_POST['allocation_3']) 
    && !empty($_POST['allocation_4'])) {

    for ($i = 1; $i < 5; $i++) {
      $stock_symbol_array[$i] = strtoupper($_POST['stock_' . $i]);
      $stock_allocation_array[$i] = $_POST['allocation_' . $i] * $_POST['invest'];

      $file_address_construct = 'http://ichart.finance.yahoo.com/table.csv?s=' . $stock_symbol_array[$i]
          . '&a=00&b=1&c=2011&d=11&e=30&f=2011&g=d&ignore=.csv';

      $file_address[$i] = $file_address_construct;


      if (!$file_handle[$i] = @fopen($file_address[$i], 'r')) {
        $stocks_not_found .= $stock_symbol_array[$i] . ' ';
      }
    }

    // SPY Benchmark
    $file_handle[5] = @fopen('http://ichart.finance.yahoo.com/table.csv?s=SPY&a=00&b=1&c=2011&d=11&e=30&f=2011&g=d&ignore=.csv', 'r');

    if (!isset($stocks_not_found)) {

      // Getting the dates
      $n = 0;
      $date_file = fopen('a.csv', 'r');
      while (($cell_array = fgetcsv($date_file)) !== false) {
        if ($n !== 0) {
        $date_array[$n] = $cell_array[0];
        }
        $n++;
      }
      $date_array = array_reverse($date_array);

      // Getting the prices
      $n = 0;
      for ($i = 1; $i < 6; $i++) {
        while (($cell_array = fgetcsv($file_handle[$i])) !== false) {
          if ($n !== 0) {
           $daily_price_array[$n] = $cell_array[6];
         }
         $n++;
        }
        $stock_price_array[$i] = array_reverse($daily_price_array);
      }

      // Calculating the cumulative returns
      for ($i = 1; $i < 6; $i++) {
        $cumulative_return_array[0] = 100;
        $daily_price_array = $stock_price_array[$i];
        for ($n = 1; $n < 252; $n++) {
          $cumulative_return_array[$n] = $daily_price_array[$n] / $daily_price_array[0];
          $cumulative_return_array[$n] = $cumulative_return_array[$n] * 100;
        }
        $stock_cumulative_array[$i] = $cumulative_return_array;
      }

      // Calculating the investment returns
      for ($i = 1; $i < 5; $i++) {
        $cumulative_return_array = $stock_cumulative_array[$i];
        $daily_price_array = $stock_price_array[$i];
        for ($n = 0; $n < 252; $n++) {
          $investment_return_array[$n] = $cumulative_return_array[$n] / 100 * $stock_allocation_array[$i];
        }
        $stock_investment_return[$i] = $investment_return_array;
      }

      // Calculating the fund investments
      for ($n = 0; $n < 252; $n++) {
        $stock_returns_1 = $stock_investment_return[1];
        $stock_returns_2 = $stock_investment_return[2];
        $stock_returns_3 = $stock_investment_return[3];
        $stock_returns_4 = $stock_investment_return[4];
        $fund_total_investment[$n] = $stock_returns_1[$n] + $stock_returns_2[$n] + $stock_returns_3[$n] + $stock_returns_4[$n];
      }

      // Calculating the fund cumulative returns
      for ($n = 0; $n < 252; $n++) {
          $cumulative_fund_array[$n] = $fund_total_investment[$n] / $fund_total_investment[0];
          $cumulative_fund_array[$n] = $cumulative_fund_array[$n] * 100;
      }

      // Calculating the fund daily returns
      $fund_daily_returns_array[0] = 0;
      for ($n = 1; $n < 252; $n++) {
        $fund_daily_returns_array[$n] = ($cumulative_fund_array[$n] / $cumulative_fund_array[$n - 1] - 1) * 100;
      }

      // Calculate metrics
      $annual_return = (($cumulative_fund_array[251] / $cumulative_fund_array[0]) - 1) * 100;
      $avg_daily_return = array_sum($fund_daily_returns_array) / 252;
      $stdev_daily_return = sd($fund_daily_returns_array);
      $sharpe_ratio = sqrt(252) * $avg_daily_return / $stdev_daily_return;

    }
  } else {
    $input_error = true;
  }
}
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8'>
    <title>Portfolio Tool by Sean Duffy</title>

    <!-- Le styles -->
    <link href='css/bootstrap.css' rel='stylesheet'>
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */      

      }
      .anchor {
        display: block;
        height: 60px; 
        margin-top: -60px; 
        visibility: hidden;
      }
      #getdata {
        margin-top: 5px;
      }
    </style>
    <link href='css/bootstrap-responsive.css' rel='stylesheet'>

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src='http://html5shim.googlecode.com/svn/trunk/html5.js'></script>
    <![endif]-->

    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>

      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          <?php
            echo "['Date', '" . $stock_symbol_array[1] . "', '" . $stock_symbol_array[2] . "', '"
            . $stock_symbol_array[3] . "', '" . $stock_symbol_array[4] . "', 'SPY'],";

            $n = 0;
            foreach ($date_array as $date) {
              echo "['" . $date  . "'";
              for ($i = 1; $i < 6; $i++) {
                $cumulative_list = $stock_cumulative_array[$i];
                echo ", " . $cumulative_list[$n];
              }
              echo "], ";
              $n++;
            }
            
          ?>
        ]);

        var options = {
          title: 'Cumulative Return Through 2011',
          'height': 400,
          'width': 640,
          chartArea:{left:80,top:30}
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }

    </script>

  </head>

  <body data-spy='scroll' data-target='.navbar' data-offset='60'>

    <div class='navbar navbar-inverse navbar-fixed-top'>
      <div class='navbar-inner'>
        <div class='container'>
          <a class='btn btn-navbar' data-toggle='collapse' data-target='.nav-collapse'>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
          </a>
          <a class='brand' href='#'>Portfolio Tool</a>
          <div class='nav-collapse collapse'>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class='container'>
      <section id='select_equities' class='anchor'></section>
      <div class='row'>
        <div class='span6'>
          <form class='form' method='post' action='index.php' name='select_equities'>
            <legend>Select Equities</legend>
            <p>Enter the stock symbols of four equities and for each specify your desired weighting in the form of
              a decimal, the total of all four weightings should equal one.</p>
            <?php
            if ($input_error) {
              echo '<div class="alert alert-error"><strong>Error!</strong> Please fill in all fields.</div>';
            }
            if (!empty($stocks_not_found)) {
              echo '<div class="alert alert-error">The following stocks were not found: <strong>' . $stocks_not_found
              . '</strong></div>';
            }
            ?>
            <label>Equity 1</label>
            <input type='text' name='stock_1' placeholder='Enter stock symbol'>
            <input type='text' name='allocation_1' placeholder='Enter fraction'>
            <label>Equity 2</label>
            <input type='text' name='stock_2' placeholder='Enter stock symbol'>
            <input type='text' name='allocation_2' placeholder='Enter fraction'>
            <label>Equity 3</label>
            <input type='text' name='stock_3' placeholder='Enter stock symbol'>
            <input type='text' name='allocation_3' placeholder='Enter fraction'>
            <label>Equity 4</label>
            <input type='text' name='stock_4' placeholder='Enter stock symbol'>
            <input type='text' name='allocation_4' placeholder='Enter fraction'>
            <div class='input-prepend'>
              <label>Total Investment</label>
              <span class='add-on'>$</span><input class='span2' type='text' name='invest' value='1000000'>
            </div>
            <button type='submit' name='getdata-submit' class='btn btn-primary' id='getdata'>Get Data</button>
          </form>
        </div>
        <div class='span6'>
          <?php if (!isset($stocks_not_found) && isset($_POST['getdata-submit']) && !$input_error): ?>
          <ul class='nav nav-tabs' id='upperTab'>
              <li class='active'><a href='#metrics' data-toggle='tab'>Metrics</a></li>
              <li><a href='#graphs' data-toggle='tab'>Graphs</a></li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane in active' id='metrics'>
                <table class='table table-bordered'>
                  <tbody>
                    <tr>
                      <th>Annual Return</th>
                      <?php echo '<td>' . $annual_return . '%</td>'; ?>
                    </tr>
                    <tr>
                      <th>Average Daily Return</th>
                      <?php echo '<td>' . $avg_daily_return . '%</td>'; ?>
                    </tr>
                    <tr>
                      <th>StDev Daily Return</th>
                      <?php echo '<td>' . $stdev_daily_return . '%</td>'; ?>
                    </tr>
                    <tr>
                      <th>Sharpe Ratio</th>
                      <?php echo '<td>' . $sharpe_ratio . '</td>'; ?>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class='tab-pane' id='graphs'>
                <div id='chart_div' style='overflow:hidden;'></div>
              </div>
        </div>
      </div>
      <section id='data_tables' class='anchor'></section>
        <div class='row'>
          <div class='span12'>
            <legend>Data Tables</legend>
            <ul class='nav nav-tabs' id='myTab'>
              <li class='active'><a href='#stock_data' data-toggle='tab'>Stock Data</a></li>
              <li><a href='#fund_data' data-toggle='tab'>Fund Data</a></li>
            </ul>
            <div class='tab-content'>
              <div class='tab-pane in active' id='stock_data'>
                <table class='table table-bordered'>
                  <thead>
                    <tr>
                      <th>Date</th>
                      <?php
                        for ($i = 1; $i < 5; $i++) {
                          echo '<th>' . $stock_symbol_array[$i] . ' Price</th>';
                          echo '<th>' . $stock_symbol_array[$i] . ' Cumu Rtn</th>';
                          echo '<th>' . $stock_symbol_array[$i] . ' Invest</th>';
                        }
                      ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $n = 0;
                      foreach ($date_array as $date) {
                        echo '<tr><td>' . $date . '</td>';
                        for ($i = 1; $i < 5; $i++) {
                          $price_list = $stock_price_array[$i];
                          $cumulative_list = $stock_cumulative_array[$i];
                          $investment_return_list = $stock_investment_return[$i];
                          echo '<td>$' . $price_list[$n] . '</td>';
                          echo '<td>' . number_format($cumulative_list[$n], 3, '.', '') . '%</td>';
                          echo '<td>$' . number_format($investment_return_list[$n], 3, '.', '') . '</td>';
                        }
                        echo '</tr>';
                        $n++;
                      }
                    ?>
                  </tbody>
                </table>
              </div>
              <div class='tab-pane' id='fund_data'>
                <table class='table table-bordered'>
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Fund Invest</th>
                      <th>Fund Cumulative</th>
                      <th>Fund Daily Return</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $n = 0;
                      foreach ($date_array as $date) {
                        echo '<tr><td>' . $date . '</td>';
                        echo '<td>$' . $fund_total_investment[$n] . '</td>';
                        echo '<td>' . $cumulative_fund_array[$n] . '%</td>';
                        echo '<td>' . $fund_daily_returns_array[$n] . '%</td>';
                        echo '</tr>';
                        $n++;
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </section>
    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src='http://code.jquery.com/jquery-latest.js'></script>
    <script src='js/bootstrap-transition.js'></script>
    <script src='js/bootstrap-alert.js'></script>
    <script src='js/bootstrap-modal.js'></script>
    <script src='js/bootstrap-dropdown.js'></script>
    <script src='js/bootstrap-scrollspy.js'></script>
    <script src='js/bootstrap-tab.js'></script>
    <script src='js/bootstrap-tooltip.js'></script>
    <script src='js/bootstrap-popover.js'></script>
    <script src='js/bootstrap-button.js'></script>
    <script src='js/bootstrap-collapse.js'></script>
    <script src='js/bootstrap-carousel.js'></script>
    <script src='js/bootstrap-typeahead.js'></script>

  </body>
</html>