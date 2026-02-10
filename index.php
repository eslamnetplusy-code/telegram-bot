<?php
http_response_code(200);

file_put_contents("hit.txt", "HIT\n", FILE_APPEND);

echo "OK";
