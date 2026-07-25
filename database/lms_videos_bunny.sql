-- Vídeos Bunny no LMS + origem heartbeat
-- Cole no phpMyAdmin após escola_integracoes_bunny.sql

ALTER TABLE `lms_videos`
  MODIFY COLUMN `provider` ENUM('youtube','private','bunny') NOT NULL DEFAULT 'youtube';

ALTER TABLE `lms_videos`
  ADD COLUMN `bunny_video_id` VARCHAR(64) NULL DEFAULT NULL AFTER `provider`;

ALTER TABLE `lms_videos`
  ADD COLUMN `bunny_status` ENUM('uploading','processing','ready','error') NULL DEFAULT NULL AFTER `bunny_video_id`;

ALTER TABLE `lms_videos`
  ADD COLUMN `bunny_error` VARCHAR(500) NULL DEFAULT NULL AFTER `bunny_status`;

-- Heartbeat de estudo (se a tabela existir)
ALTER TABLE `lms_estudo_sessao`
  MODIFY COLUMN `origem` ENUM('presence','youtube','private','bunny') NOT NULL DEFAULT 'presence';
