<?php

// для теста метода HttpRequestTest - testPost
if (!empty($_GET['post']) && $_GET['post'] == 1)
{
    if (!empty($_FILES['param2']) && $_FILES['param2']['error'] === UPLOAD_ERR_OK)
    {
	$image = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_img.jpg';
	if (file_exists($image))
	    unlink($image);


	move_uploaded_file($_FILES['param2']['tmp_name'], $image);
    }
    exit(http_build_query($_POST));
}

if (!empty($_GET['put']) && $_GET['put'] == 1)
{
    $fileput = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_put.txt';
    $putdata = fopen("php://input", "r");
    $fp = fopen($fileput, "w");
    while ($data = fread($putdata, 1024))
	fwrite($fp, $data);
    fclose($fp);
    fclose($putdata);

    exit($fileput);
}

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