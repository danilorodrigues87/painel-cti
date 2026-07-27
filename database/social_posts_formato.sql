-- Formatos de post social: feed | story | reel | carousel
-- Cole no phpMyAdmin (tabela social_posts já existente)

ALTER TABLE `social_posts`
  ADD COLUMN `formato` VARCHAR(20) NOT NULL DEFAULT 'feed'
    COMMENT 'feed|story|reel|carousel'
    AFTER `canais`;
