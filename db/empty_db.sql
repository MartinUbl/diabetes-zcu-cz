PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table: download_counter
CREATE TABLE `download_counter` (`name` VARCHAR NOT NULL, `month` INTEGER NOT NULL, `year` INTEGER NOT NULL, `counter` INTEGER NOT NULL, PRIMARY KEY (`name`,`month`,`year`));

-- Table: download_token
CREATE TABLE `download_token` (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `category` VARCHAR NOT NULL, `token` VARCHAR NOT NULL, `valid_to` DATETIME_TEXT NOT NULL, `used` BOOLEAN_INTEGER NOT NULL DEFAULT 0, `requester_name` VARCHAR NOT NULL, `requester_phone` VARCHAR NOT NULL, `requester_address` VARCHAR NOT NULL, `requester_email` VARCHAR NOT NULL, `requester_ip` VARCHAR NOT NULL);

-- Table: experiment
CREATE TABLE `experiment` (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `name` VARCHAR NOT NULL);

-- Table: experiment_participant
CREATE TABLE "experiment_participant" (`experiment_id` INTEGER NOT NULL, `users_id` INTEGER NOT NULL, PRIMARY KEY (`experiment_id`,`users_id`), FOREIGN KEY (`experiment_id`) REFERENCES `experiment` (`id`), FOREIGN KEY (`users_id`) REFERENCES `users` (`id`));

-- Table: global_states
CREATE TABLE `global_states` (`key` VARCHAR NOT NULL, `value` VARCHAR NULL, PRIMARY KEY (`key`));

-- Table: icarus_uploads
CREATE TABLE `icarus_uploads` (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `upload_id` VARCHAR NULL, `score` INTEGER NOT NULL, `player_qualification` INTEGER NOT NULL, `disease_code` VARCHAR NOT NULL, `nickname` VARCHAR NOT NULL, `difficulty` INTEGER NOT NULL, `date` DATETIME_TEXT NOT NULL);

-- Table: news
CREATE TABLE "news" (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `title` VARCHAR NOT NULL, `short_content` VARCHAR NOT NULL, `full_content` TEXT NULL, `users_id` INTEGER NOT NULL, `published_on` DATETIME_TEXT NOT NULL, `social_media_posted` BOOLEAN_INTEGER NOT NULL DEFAULT 0, FOREIGN KEY (`users_id`) REFERENCES `users` (`id`));

-- Table: phinxlog
CREATE TABLE `phinxlog` (`version` BIGINTEGER NOT NULL, `migration_name` VARCHAR(100) NULL, `start_time` TIMESTAMP_TEXT NULL, `end_time` TIMESTAMP_TEXT NULL, `breakpoint` BOOLEAN_INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (`version`));

-- Table: uploads
CREATE TABLE "uploads" (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `date` DATETIME_TEXT NOT NULL, `device_info` VARCHAR NOT NULL, `upload_id` VARCHAR NULL, `users_id` INTEGER NOT NULL, `experiment_id` INTEGER NULL, `checksum` VARCHAR(64) NULL, FOREIGN KEY (`users_id`) REFERENCES `users` (`id`), FOREIGN KEY (`experiment_id`) REFERENCES `experiment` (`id`));

-- Table: uploads_statistics
CREATE TABLE "uploads_statistics" (`uploads_id` INTEGER NOT NULL, `last_measurement_date` DATETIME_TEXT NULL, `current_ig` FLOAT NULL, `average_ig` FLOAT NULL, `average_ig_24h` FLOAT NULL, `time_in_range` FLOAT NULL, `time_in_range_24h` FLOAT NULL, PRIMARY KEY (`uploads_id`), FOREIGN KEY (`uploads_id`) REFERENCES `uploads` (`id`));

-- Table: users
CREATE TABLE "users" (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `email` VARCHAR NOT NULL, `password` VARCHAR NOT NULL, `role` VARCHAR NOT NULL DEFAULT 'user', `api_token` VARCHAR NOT NULL, `api_token_valid_to` DATETIME_TEXT NOT NULL, `first_name` VARCHAR NULL, `last_name` VARCHAR NULL, `birthdate` DATE_TEXT NULL, `diagnosis` VARCHAR NULL, `activation_token` VARCHAR NULL, `activation_token_valid_to` DATETIME_TEXT NULL, `password_reset_token` VARCHAR NULL, `password_reset_token_valid_to` DATETIME_TEXT NULL);

-- Index: download_token_category_token_index
CREATE INDEX `download_token_category_token_index` ON `download_token` (`category` ASC,`token` ASC);

-- Index: icarus_uploads_difficulty_index
CREATE INDEX `icarus_uploads_difficulty_index` ON `icarus_uploads` (`difficulty` ASC);

-- Index: icarus_uploads_upload_id_index
CREATE INDEX `icarus_uploads_upload_id_index` ON `icarus_uploads` (`upload_id` ASC);

-- Index: uploads_upload_id_index
CREATE INDEX `uploads_upload_id_index` ON `uploads` (`upload_id` ASC);

-- Index: users_password_reset_token_index
CREATE INDEX `users_password_reset_token_index` ON `users` (`password_reset_token` ASC);

COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
