<?php
header("Content-Type: text/plain");
foreach (["PATH_INFO","ORIG_PATH_INFO","REQUEST_URI","SCRIPT_NAME","PHP_SELF"] as $k) {
  echo $k . "=" . ($_SERVER[$k] ?? "(unset)") . "\n";
}
