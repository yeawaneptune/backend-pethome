<?php
header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "status" => true,
    "message" => "PetHome API is running",
    "version" => "1.0.0",
    "endpoints" => [
        "GET  /server/api/read.php",
        "POST /server/api/login.php",
        "POST /server/api/register.php",
        "POST /server/api/add_pet.php",
        "POST /server/api/update_pet.php",
        "POST /server/api/delete_pet.php",
        "POST /server/api/adopt_pet.php",
        "GET  /server/api/favorites.php",
        "POST /server/api/favorites.php",
        "GET  /server/api/my_requests.php",
        "GET  /server/api/stats.php",
        "GET  /server/api/notifications.php",
        "GET  /server/api/get_users.php",
        "POST /server/api/update_user.php",
        "POST /server/api/delete_user.php"
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
