<?php

// Function to calculate Greatest Common Divisor (GCD)
function gcd($a, $b) {
  if ($b == 0) return abs($a);
  return gcd($b, $a % $b);
}

// Function to convert decimal to fraction
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
      if ($b == $a) break; // Avoid division by zero if $b is an integer
      // Add a check for $b - $a being very close to zero to prevent division by a very small number.
      if (abs($b - $a) < $tolerance) break; 
      $b = 1/($b - $a);
  } while (abs($decimal - $h1/$k1) > $decimal * $tolerance);

  $numerator = $h1 * $sign;
  $denominator = $k1;
  $common_divisor = gcd($numerator, $denominator); // Renamed $g to $common_divisor for clarity
  return [intval($numerator / $common_divisor), intval($denominator / $common_divisor)];
}

$test_cases = [
  "20%50",
  "20%50+10",
  "10+20%50",
  "10%",
  "12.5%50",
  "50+12.5%50",
  "10*20%50",
  "10/0",
  "abc+123",
  "10+"
];

echo "Starting Calculator Logic Tests...\n\n";

foreach ($test_cases as $input_expression_for_test) {
  // Initialize variables for each test run
  $expression = $input_expression_for_test; // This is the variable the logic block will use and modify
  $resultDecimal = null;
  $error = "";

  echo "Processing Input: \"$expression\"\n";

  // --- Start of core processing logic from index.php ---
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
      
      // Suppress warnings for division by zero, handle it as an error instead
      // Also suppress other potential warnings during eval
      error_reporting(E_ALL & ~E_WARNING);
      set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error, $clean_expr) {
          if (strpos($errstr, "Division by zero") !== false) {
              $error = "Error evaluando la expresión."; // Consistent with other eval errors
              return true; // Mark as handled
          }
          // Check for other eval-related errors that might manifest as warnings
          if (strpos($errfile, 'eval()') !== false || strpos($errfile, 'php shell code') !== false ) {
               // For incomplete expressions like "10+" or invalid operations
              if (preg_match('/[+\-*\/%]$/', $clean_expr) || !preg_match('/^((\d+\.?\d*)|(\(.*\)))(([+\-*\/%]((\d+\.?\d*)|(\(.*\))))*)$/', $clean_expr)) {
                  $error = "Error evaluando la expresión.";
                  return true;
              }
          }
          return false; // Let other errors be handled by PHP's default handler
      });

      $calc_res = null;
      // Use @ to suppress direct output of eval errors, we check $calc_res and $error later
      eval("\$calc_res = @($clean_expr);");
      
      restore_error_handler();
      error_reporting(E_ALL); // Restore normal error reporting

      if ($error === "") { // Proceed if no error was set by the custom handler
          if (!is_numeric($calc_res) || is_infinite($calc_res) || is_nan($calc_res)) {
            // If eval resulted in non-numeric, infinite, or NaN, it's an error.
            // This also catches cases where $clean_expr was empty or just an operator.
            $error = "Expresión inválida o resultado no numérico.";
            // Specifically for incomplete expressions like "10+" which eval might not throw, but result in null/non-numeric.
             if (preg_match('/[+\-*\/%]$/', $clean_expr) && ($calc_res === null || !is_numeric($calc_res))) {
                 $error = "Error evaluando la expresión.";
            }
          } else {
            $resultDecimal = floatval($calc_res);
            $expression = ''; // Clear expression after successful calculation
          }
      }
    } catch (ParseError $e) { // Catch syntax errors from eval specifically
        $error = "Error evaluando la expresión.";
    } catch (DivisionByZeroError $e) { // Catch explicit division by zero errors
        $error = "Error evaluando la expresión.";
    } catch (Throwable $e) { // Catch any other general errors
        // More generic error for other throwables if not already set
        if ($error === "") {
             $error = "Error evaluando la expresión.";
        }
    }
  } else {
    // If the initial sanitization preg_match fails
    $error = "Expresión contiene caracteres inválidos.";
  }
  // --- End of core processing logic ---

  echo "  Result Decimal: " . ($resultDecimal !== null ? number_format($resultDecimal, 8) : "null") . "\n";
  echo "  Error: \"$error\"\n";
  echo "  Final Expression: \"$expression\"\n"; // This is $expression from the outer scope of the test case
  echo "------------------------------------\n";
}

echo "\nCalculator Logic Tests Completed.\n";

?>
