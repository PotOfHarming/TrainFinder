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
    }
?>