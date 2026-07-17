<?php
header("Content-Type: text/plain");
echo "PATH_INFO=".(["PATH_INFO"] ?? "(unset)")."\n";
echo "ORIG_PATH_INFO=".(["ORIG_PATH_INFO"] ?? "(unset)")."\n";
echo "REQUEST_URI=".(["REQUEST_URI"] ?? "")."\n";
echo "SCRIPT_NAME=".(["SCRIPT_NAME"] ?? "")."\n";
echo "PHP_SELF=".(["PHP_SELF"] ?? "")."\n";
