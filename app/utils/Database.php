<?php
    class Database {
        /* Create database class */
        private PDO $pdo;
        public function __construct() {
            $user = "root";
            $pass = "";
            $host = "127.0.0.1";
            $data = "TrainRadar";

            try {
                $this->pdo = new PDO("mysql:host=$host;dbname=$data;charset=utf8", $user, $pass);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  

                $this->generateTables();
            } catch (PDOException $e) {
                die("Error when connecting to database: " . $e->getMessage());
            }
        }

        /* If tables do not exist yet, generate them for saving */
        private function generateTables() {
            $tables = [
                "CREATE TABLE IF NOT EXISTS railways (
                    start_name VARCHAR(10),
                    start_name_long VARCHAR(100),
                    end_name VARCHAR(10),
                    end_name_long VARCHAR(100),
                    coordinates LONGTEXT
                );",

                "CREATE TABLE IF NOT EXISTS stations (
                    station_name VARCHAR(100) NOT NULL,
                    station_type VARCHAR(25),
                    code VARCHAR(10),
                    cdcode INT,
                    uiccode VARCHAR(15),
                    has_facilities BOOLEAN,
                    has_travelassistence BOOLEAN,
                    country VARCHAR(10),
                    lat DECIMAL(9, 6) NOT NULL,
                    lon DECIMAL(9, 6) NOT NULL,
                    tracks INT
                );"
            ];

            foreach ($tables as $table) {
                $this->pdo->exec($table);
            }
        }

        /* Returns database connection as PDO */
        public function getConnection(): PDO {
            return $this->pdo;
        }
    }
?>