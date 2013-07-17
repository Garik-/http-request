<?php
echo "GET\n";
print_r($_GET);
echo "POST\n";
print_r($_POST);
echo "FILES\n";
print_r($_FILES);
echo "REQUEST HEADERS\n";
print_r(apache_request_headers());
echo "RESPONSE HEADERS\n";
print_r(apache_response_headers());
echo "SERVER\n";
print_r($_SERVER);