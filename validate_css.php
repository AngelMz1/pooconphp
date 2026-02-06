<?php
$css = file_get_contents('assets/css/styles.css');
$lines = explode("\n", $css);
$balance = 0;
$errorLine = -1;

foreach ($lines as $i => $line) {
    // Remove comments roughly
    $line = preg_replace('/\/\*.*?\*\//s', '', $line); 
    // Note: This simple regex doesn't handle multi-line comments well if processed line by line.
    // Better to strip comments from full string first.
}

// Strip comments from full content
$cssClean = preg_replace('!/\*.*?\*/!s', '', $css);
$len = strlen($cssClean);
$lineNum = 1;

for ($i = 0; $i < $len; $i++) {
    $char = $cssClean[$i];
    if ($char === "\n") $lineNum++;
    if ($char === '{') $balance++;
    if ($char === '}') {
        $balance--;
        if ($balance < 0) {
             echo "Error: Unexpected closing brace at line ~$lineNum\n";
             exit;
        }
    }
}

if ($balance !== 0) {
    echo "Error: Unbalanced braces. Balance: $balance (Positive means missing closing brace)\n";
} else {
    echo "CSS Syntax check: Braces look balanced.\n";
}
