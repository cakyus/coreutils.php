<?php

// Compare two files by line
//
// The original "comm" is too confusing
//
// Usage: $0 OPTIONS FILE1 FILE2
//
// Options:
// -10 : print lines which only exists in FILE1
// -01 : print lines which only exists in FILE2
// -11 : print lines which exists in both
//
// Notes:
//  * Empty lines is ignored
//  * Handle unsorted lines
//
// Discussion:
//  * Compare case in-sentive ?
//

$options = array(); // 01 10 11
$file1 = null;
$file2 = null;

for ($i = 1; $i < $_SERVER['argc']; $i++) {
  if ($_SERVER['argv'][$i] == '-01') {
    array_push($options, '01');
  } elseif ($_SERVER['argv'][$i] == '-10') {
    array_push($options, '10');
  } elseif ($_SERVER['argv'][$i] == '-11') {
    array_push($options, '11');
  } elseif (is_null($file1) == true) {
    $file1 = $_SERVER['argv'][$i];
  } elseif (is_null($file2) == true) {
    $file2 = $_SERVER['argv'][$i];
  } else {
    fwrite(STDERR, "ERROR Invalid command line arguments.\n"); exit(1);
  }
}

if (is_null($file1) == true) {
  fwrite(STDERR, "ERROR FILE1 is not defined.\n"); exit(1);
} elseif (is_null($file2) == true) {
  fwrite(STDERR, "ERROR FILE2 is not defined.\n"); exit(1);
} elseif (is_file($file1) == false) {
  fwrite(STDERR, "ERROR FILE1 is not exits.\n"); exit(1);
} elseif (is_file($file2) == false) {
  fwrite(STDERR, "ERROR FILE2 is not exits.\n"); exit(1);
} elseif (count($options) == 0) {
  fwrite(STDERR, "ERROR options not defined.\n"); exit(1);
}

$lines1 = file($file1, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines2 = file($file2, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$lines = array(
    '10' => array()
  , '01' => array()
  , '11' => array()
  );

foreach ($lines1 as $line1) {
  if (in_array($line1, $lines2) == true) {
    array_push($lines['11'], $line1);
  } else {
    array_push($lines['10'], $line1);
  }
}

foreach ($lines2 as $line2) {
  if (in_array($line2, $lines1) == false) {
    array_push($lines['01'], $line2);
  }
}

foreach ($lines as $option => $option_lines) {
  if (in_array($option, $options) == false) {
    continue;
  }
  if (count($option_lines) == 0) {
    continue;
  }
  echo(implode(PHP_EOL, $option_lines).PHP_EOL);
}

exit(0);

