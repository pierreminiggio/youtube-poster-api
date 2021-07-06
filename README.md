```sql
CREATE TABLE `youtube_poster_api`.`youtube_account` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `channel_id` VARCHAR(255) NOT NULL,
    `google_login` VARCHAR(255) NOT NULL,
    `google_password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```