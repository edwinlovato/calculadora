<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Calculadora</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Make buttons squares and scale nicely */
    .btn-calc {
      //aspect-ratio: 1 / 1;
		padding: 1rem 0;
		text-align: center;
		user-select: none;
	  min-width: 3rem;
    }
    /* Calculator container max width */
    #calculator {
      max-width: 360px;
    }
    /* Smaller font for fraction */
    .fraction {
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 min-h-screen flex items-center justify-center p-4">
  <div id="calculator" class="bg-white bg-opacity-90 rounded-lg shadow-xl p-6 w-full max-w-md">
    <h1 class="text-center text-3xl font-bold mb-6 text-gray-800 select-none"></h1>
    <?php
    function gcd($a, $b) {
      if ($b == 0) return abs($a);
      return gcd($b, $a % $b);
    }

    function decimalToFraction($decimal, $tolerance = 1.0E-6) {
      if (intval($decimal) == $decimal) {
        return [$decimal, 1];
      }
      $sign = ($decimal < 0) ? -1 : 1;
      $decimal = abs($decimal);
      $h1=1; $h2=0;
      $k1=0; $k2=1;
      $b = $decimal;
      do {
          $a = floor($b);
          $aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
          $aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
          if ($b == $a) break;
          $b = 1/($b - $a);
      } while (abs($decimal - $h1/$k1) > $decimal * $tolerance);

      $numerator = $h1 * $sign;
      $denominator = $k1;
      $g = gcd($numerator, $denominator);
      return [intval($numerator / $g), intval($denominator / $g)];
    }

    $resultDecimal = null;
    $error = "";
    $expression = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $expression = $_POST['expression'] ?? '';
      // Sanitize expression: allow only digits, decimal point, operators + - * / % and spaces
      if (preg_match('/^[0-9+\-*\/%.\s]+$/', $expression)) {
        // Step 1: Primary Transformation for "A % B"
        $expr_for_eval = preg_replace_callback(
          '/(\d+(?:\.\d+)?)\s*%\s*(\d+(?:\.\d+)?)/', // Regex for "A % B"
          function($m) {
            // Ensure A and B are treated as numbers, important for the division and multiplication
            $A = floatval($m[1]);
            $B = floatval($m[2]);
            return '(' . $A . '/100*' . $B . ')'; // Parenthesized for order of operations
          },
          $expression // Input is the original expression
        );

        // Step 2: Secondary Transformation for "X%"
        // Process the result of Step 1 for any remaining standalone percentages
        $expr_for_eval = str_replace('%', '/100', $expr_for_eval);

        try {
          // Remove all whitespace for evaluation
          $clean_expr = preg_replace('/\s+/', '', $expr_for_eval);
          $calc_res = null;
          eval("\$calc_res = $clean_expr ;");
          if (!is_numeric($calc_res) || is_infinite($calc_res) || is_nan($calc_res)) {
            $error = "Expresión inválida o resultado no numérico.";
          } else {
            $resultDecimal = floatval($calc_res);
            $expression = ''; // Clear expression after successful calculation
          }
        } catch (Throwable $e) {
          $error = "Error evaluando la expresión.";
        }
      } else {
        $error = "Expresión contiene caracteres inválidos.";
      }
    }
    ?>
    <form method="post" id="calc-form" class="select-none" autocomplete="off">
      <input 
        id="display" name="expression" type="text" 
        class="w-full bg-gray-100 rounded-md text-right text-3xl p-3 font-mono border border-gray-300 mb-2 focus:outline-none focus:ring-4 focus:ring-indigo-400" 
        value="<?=htmlspecialchars($expression)?>"
        readonly
      />

      <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded font-semibold text-center select-text"><?=htmlspecialchars($error)?></div>
      <?php elseif ($resultDecimal !== null): 
        list($numer, $denom) = decimalToFraction($resultDecimal);
      ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded font-semibold text-center space-y-1 select-text">
          <div>Decimal: <span class="font-mono"><?=number_format($resultDecimal, 6)?></span></div>
          <div class="fraction">Fracción: 
            <span class="font-mono">
            <?php 
            if ($denom == 1) {
              echo $numer;
            } else {
              if (abs($numer) > $denom) {
                $whole = intdiv($numer, $denom);
                $remainder = abs($numer % $denom);
                if ($remainder == 0) {
                  echo $whole;
                } else {
                  echo "{$whole} {$remainder}/{$denom}";
                }
              } else {
                echo "{$numer}/{$denom}";
              }
            }
            ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-4 gap-2">
        <button type="button" class="btn-calc col-span-2 bg-red-500 hover:bg-red-600 text-white font-bold rounded shadow" id="btn-clear" aria-label="Limpiar">C</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="%">%</button>
        <button type="button" class="btn-calc bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded" data-value="+">+</button>
        

        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="7">7</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="8">8</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="9">9</button>
        <button type="button" class="btn-calc bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded" data-value="-">−</button>		

        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="4">4</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="5">5</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="6">6</button>        
		<button type="button" class="btn-calc bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded" data-value="*">×</button>

        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="1">1</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="2">2</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="3">3</button>
        <button type="button" class="btn-calc bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded" data-value="/">÷</button>
		
        <button type="button" class="btn-calc col-span-2 bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value="0">0</button>
        <button type="button" class="btn-calc bg-gray-200 hover:bg-gray-300 rounded font-bold" data-value=".">.</button>
        <button type="submit" class="btn-calc bg-green-600 hover:bg-green-700 text-white font-bold rounded" aria-label="Igual">=</button>
      </div>
    </form>

  <script>
    (function() {
      const display = document.getElementById('display');
      const buttons = document.querySelectorAll('button[data-value]');
      const btnClear = document.getElementById('btn-clear');

      const allowedChars = /[0-9+\-*\/%.]/;

      buttons.forEach(button => {
        button.addEventListener('click', () => {
          const value = button.getAttribute('data-value');

          if (value === '.') {
            let parts = display.value.split(/[\+\-\*\/%]/);
            let lastPart = parts[parts.length -1];
            if (lastPart.includes('.')) {
              return;
            }
          }

          if (allowedChars.test(value)) {
            display.value += value;
          }
        });
      });

      btnClear.addEventListener('click', () => {
        display.value = '';
      });
    })();
  </script>
  </div>
</body>
</html>
