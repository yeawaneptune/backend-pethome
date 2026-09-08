<?php
class Database {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $db;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
        $this->port = (int)(getenv('DB_PORT') ?: 4000);
        $this->user = getenv('DB_USER') ?: 'teS1x9n61Xk9FhY.root';
        $this->pass = getenv('DB_PASS') ?: 'Hl9TkVnCxzmVftoe';
        $this->db   = getenv('DB_NAME') ?: 'test';
    }

    public $conn;

    public function connect() {
        // Allow CORS for Flutter Web / local development
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $this->conn = new mysqli();

        // TiDB Cloud requires SSL
        $this->conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
        $this->conn->real_connect(
            $this->host,
            $this->user,
            $this->pass,
            $this->db,
            $this->port,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if ($this->conn->connect_error) {
            die(json_encode([
                'status'  => false,
                'message' => 'Database Connection Failed: ' . $this->conn->connect_error
            ], JSON_UNESCAPED_UNICODE));
        }

        $this->conn->set_charset('utf8mb4');
        return $this->conn;
    }
}
?>