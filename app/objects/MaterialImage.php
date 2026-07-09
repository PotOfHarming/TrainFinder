<?php
    class MaterialImage {
        public string $type;
        public string $url;
        public int $width;
        public int $height;

        public function __construct(string $type, string $url, int $width, int $height) {
            $this->type = $type;
            $this->url = $url;
            $this->width = $width;
            $this->height = $height;
        }

        /* Save material image to database */
        public function saveMaterialImage(?bool $update = false) {
            require_once(__DIR__ . "/../utils/Database.php");
            $db = new Database();

            if (materialImageExists($this->type)) {
                if (!$update) return;
                $upd_stmt = $db->getConnection()->prepare("
                        UPDATE `material_images` SET 
                            `train_type` = ?, `train_img` = ?, 
                            `img_width` = ?, `img_height` = ?
                        WHERE
                            `train_type` = ?
                ");

                $upd_stmt->execute([$this->type, $this->url, $this->width, $this->height, $this->type]);
            } else {
                $ins_stmt = $db->getConnection()->prepare("
                        INSERT INTO `material_images`(
                            `train_type`, `train_img`, `img_width`, `img_height`
                        ) VALUES (
                            ?, ?, ?, ?
                        )
                ");

                $ins_stmt->execute([$this->type, $this->url, $this->width, $this->height]);
            }
        }
    }

    /* Check if material image already exists */
    function materialImageExists(string $trainType): bool {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();

        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM material_images WHERE train_type = ?");
        $stmt->execute([$trainType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $stmt->fetchColumn();
    }

    /* Get material image from the database */
    function getMaterialImage(): ?MaterialImage {
        require_once(__DIR__ . "/../utils/Database.php");
        $db = new Database();

        $stmt = $db->getConnection()->prepare("SELECT * FROM material_images WHERE train_type = ?");
        $stmt->execute([$trainType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row == null) return null;
        return new MaterialImage(
            $row["train_type"], $row["train_img"],
            $row["img_width"], $row["img_height"]
        );
    }
?>