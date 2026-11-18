-- Izpilda tikai projekta datubāzē, pēc mm_camps izveides.
-- mm_camps.id jābūt INT UNSIGNED un tabulai jāizmanto InnoDB.
CREATE TABLE IF NOT EXISTS mm_gallery_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    camp_id INT UNSIGNED NULL,
    alt_text VARCHAR(250) NOT NULL,
    image_data MEDIUMBLOB NOT NULL,
    width SMALLINT UNSIGNED NOT NULL,
    height SMALLINT UNSIGNED NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX gallery_public (is_published, id),
    INDEX gallery_camp (camp_id, is_published, id),
    CONSTRAINT mm_gallery_camp_fk FOREIGN KEY (camp_id)
        REFERENCES mm_camps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
