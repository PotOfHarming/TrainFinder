<?php
    function getConfig(string $location): array {
        return json_decode(file_get_contents($location), true);
    }
?>