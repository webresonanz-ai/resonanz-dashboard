-- ═══════════════════════════════════════════════════════════════
--  Resonanz Music Foundation — Database Schema
--  Engine : InnoDB | Charset : utf8mb4 / utf8mb4_unicode_ci
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `resonanz_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `resonanz_db`;

-- ───────────────────────────────────────────────────────────────
--  users
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `email`      VARCHAR(255)    NOT NULL,
    `password`   VARCHAR(255)    NOT NULL         COMMENT 'bcrypt hash',
    `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE  KEY `uq_users_email` (`email`),
    INDEX        `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  schedule
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `schedule` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `day`        ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    `time_start` TIME         NOT NULL,
    `time_end`   TIME         NOT NULL,
    `course`     VARCHAR(150) NOT NULL,
    `room`       VARCHAR(100) NOT NULL,
    `teacher`    VARCHAR(100) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_schedule_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  concerts
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `concerts` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(200) NOT NULL,
    `event_date` DATE         NOT NULL,
    `event_time` TIME         NOT NULL,
    `venue`      VARCHAR(200) NOT NULL,
    `price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tag`        VARCHAR(50)  NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_concerts_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  news
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `news` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255) NOT NULL,
    `category`    VARCHAR(80)  NOT NULL,
    `excerpt`     TEXT         NOT NULL,
    `content`     LONGTEXT     NULL,
    `is_published` TINYINT(1)  NOT NULL DEFAULT 1,
    `published_at` DATETIME    NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_news_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  courses
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `courses` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)  NOT NULL,
    `level`       VARCHAR(80)   NOT NULL,
    `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `period`      VARCHAR(30)   NOT NULL DEFAULT '/month',
    `duration`    VARCHAR(80)   NOT NULL,
    `class_size`  VARCHAR(60)   NOT NULL,
    `features`    JSON          NOT NULL  COMMENT 'JSON array of feature strings',
    `is_featured` TINYINT(1)    NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
    `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  facilities  (Facilitation page)
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `facilities` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(150) NOT NULL,
    `capacity`   VARCHAR(80)  NOT NULL,
    `description` TEXT        NOT NULL,
    `icon`       VARCHAR(60)  NOT NULL DEFAULT 'Music',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  teachers
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `teachers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `role`       VARCHAR(120) NOT NULL,
    `bio`        TEXT         NOT NULL,
    `initials`   VARCHAR(4)   NOT NULL,
    `email`      VARCHAR(255) NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  contact_messages  (submissions from the Contact form)
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `read_at`    DATETIME     NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_contact_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════
--  SEED DATA
-- ═══════════════════════════════════════════════════════════════

-- Admin user
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`) VALUES (
    'Resonanz Admin',
    'admin@resonanz.org',
    '$2y$12$zMIfqJJsddGPhzWPgwfsJOKrbq3CPcb21Mk9aeIx.oXbuPIHEU6My',
    'admin'
);

-- Schedule
INSERT IGNORE INTO `schedule` (`id`,`day`,`time_start`,`time_end`,`course`,`room`,`teacher`,`sort_order`) VALUES
(1,'Monday','09:00:00','18:00:00','Classical Piano','Studio A','Ms. Elena Rossi',1),
(2,'Tuesday','10:00:00','19:00:00','Violin Masterclass','Hall B','Mr. Hiroshi Tanaka',2),
(3,'Wednesday','09:00:00','17:00:00','Vocal Training','Studio C','Ms. Amara Okafor',3),
(4,'Thursday','11:00:00','20:00:00','Music Theory','Room 204','Dr. James Whitmore',4),
(5,'Friday','09:00:00','18:00:00','String Ensemble','Hall A','Mr. Lucas Müller',5),
(6,'Saturday','08:00:00','16:00:00','Youth Orchestra','Main Hall','Ms. Sophia Chen',6);

-- Concerts
INSERT IGNORE INTO `concerts` (`id`,`title`,`event_date`,`event_time`,`venue`,`price`,`tag`) VALUES
(1,'Winter Symphony Gala','2025-12-15','19:30:00','Grand Concert Hall',45.00,'Featured'),
(2,'Young Virtuosos Showcase','2025-12-22','18:00:00','Resonanz Main Hall',25.00,'Student'),
(3,'New Year Classical Night','2026-01-01','20:00:00','City Philharmonic',60.00,'Special'),
(4,'Chamber Music Evening','2026-01-18','19:00:00','Studio A',35.00,NULL),
(5,'Spring Piano Recital','2026-02-10','19:30:00','Grand Concert Hall',40.00,NULL),
(6,'Youth Orchestra Premiere','2026-03-05','18:30:00','Resonanz Main Hall',30.00,'New');

-- News
INSERT IGNORE INTO `news` (`id`,`title`,`category`,`excerpt`,`is_published`,`published_at`) VALUES
(1,'Resonanz Students Win National Competition','Achievement','Three of our talented students took home top honors at the National Young Musicians Competition.',1,'2025-11-28 00:00:00'),
(2,'New Partnership with Vienna Conservatory','Announcement','We are proud to announce an exchange program that will allow our students to study abroad in Austria.',1,'2025-11-15 00:00:00'),
(3,'Winter Recital Series Announced','Event','Our annual winter recital series will feature over 40 performances across three weekends this December.',1,'2025-11-08 00:00:00'),
(4,'Masterclass with Renowned Pianist','Event','Grammy-winning pianist Maria Santos will host an exclusive masterclass for our advanced students.',1,'2025-10-30 00:00:00'),
(5,'New Scholarship Fund Established','Announcement','A generous donation from the Hartwell Foundation will provide full scholarships for 10 students annually.',1,'2025-10-22 00:00:00'),
(6,'Alumni Spotlight: From Resonanz to Carnegie Hall','Story','Read the inspiring journey of our alumna Sarah Kim, who recently made her Carnegie Hall debut.',1,'2025-10-10 00:00:00');

-- Courses
INSERT IGNORE INTO `courses` (`id`,`name`,`level`,`price`,`duration`,`class_size`,`features`,`is_featured`,`sort_order`) VALUES
(1,'Beginner Piano','Beginner',120.00,'45 min / session','Private','["Weekly 1-on-1 lessons","Practice materials included","Monthly progress report","Recital participation"]',0,1),
(2,'Advanced Performance','Advanced',280.00,'90 min / session','Private','["Bi-weekly lessons","Masterclass access","Concert opportunities","Competition coaching","Recording sessions"]',1,2),
(3,'Ensemble Program','Intermediate',180.00,'120 min / session','Group (8)','["Weekly group rehearsals","Performance opportunities","Music theory class","Sheet music provided"]',0,3);

-- Facilities
INSERT IGNORE INTO `facilities` (`id`,`name`,`capacity`,`description`,`icon`,`sort_order`) VALUES
(1,'Grand Concert Hall','500 seats','Acoustically perfected performance venue with a Steinway D-274 concert grand.','Music',1),
(2,'Piano Studios','12 rooms','Individual practice rooms equipped with Yamaha and Kawai grand pianos.','Piano',2),
(3,'Recording Studio','Professional','State-of-the-art recording facility with Pro Tools HD and vintage microphones.','Mic2',3),
(4,'Percussion Lab','30 students','Complete orchestral and world percussion collection with soundproofing.','Drum',4),
(5,'String Workshop','20 students','Dedicated space for strings with instrument storage and humidity control.','Guitar',5),
(6,'Lecture Theatre','120 seats','Multimedia lecture hall for theory classes, seminars, and workshops.','Speaker',6),
(7,'Climate-Controlled Storage','200+ instruments','Secure, humidity-controlled storage for student and faculty instruments.','Snowflake',7),
(8,'Innovation Lab','15 students','Digital music production, composition, and electronic music workspace.','Lightbulb',8);

-- Teachers
INSERT IGNORE INTO `teachers` (`id`,`name`,`role`,`bio`,`initials`,`email`,`sort_order`) VALUES
(1,'Elena Rossi','Piano · Department Head','Juilliard graduate with 20 years of concert experience across Europe and Asia.','ER','erossi@resonanz.org',1),
(2,'Hiroshi Tanaka','Violin · Principal','Former concertmaster of the Tokyo Symphony, specializing in Romantic repertoire.','HT','htanaka@resonanz.org',2),
(3,'Amara Okafor','Voice · Vocal Coach','Internationally touring soprano and vocal pedagogue with a passion for operatic training.','AO','aokafor@resonanz.org',3),
(4,'James Whitmore','Music Theory & Composition','PhD in Musicology and award-winning composer of contemporary chamber works.','JW','jwhitmore@resonanz.org',4),
(5,'Lucas Müller','Cello · Chamber Music','Founding member of the Müller Quartet with 15+ international recordings.','LM','lmuller@resonanz.org',5),
(6,'Sophia Chen','Orchestra Conductor','Conductor of the National Youth Orchestra with degrees from Yale and Vienna.','SC','schen@resonanz.org',6),
(7,'Marcus Rivera','Jazz & Improvisation','Grammy-nominated jazz pianist and arranger with a career spanning three decades.','MR','mrivera@resonanz.org',7),
(8,'Ingrid Larsson','Flute · Woodwinds','Principal flutist of the Nordic Philharmonic and dedicated educator.','IL','ilarsson@resonanz.org',8);
