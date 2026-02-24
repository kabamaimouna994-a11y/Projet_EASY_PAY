-- ============================================
-- EASY-PAY - Mise à jour de la BDD existante
-- À importer dans phpMyAdmin sur la base easy_pay
-- ============================================

USE `easy_pay`;

-- ----------------------------
-- MISE À JOUR TABLE : client
-- ----------------------------

-- Ajout du numéro de téléphone (obligatoire)
ALTER TABLE `client`
    ADD COLUMN `telephone` varchar(20) NOT NULL AFTER `nom_user`;

-- Agrandissement de la colonne PIN pour permettre le stockage en clair
-- (on garde le PIN tel quel comme demandé)
ALTER TABLE `client`
    MODIFY COLUMN `pin_user` varchar(10) NOT NULL;

-- Ajout de l'index unique sur le téléphone (pas deux comptes avec le même numéro)
ALTER TABLE `client`
    ADD UNIQUE KEY `telephone` (`telephone`);

-- ----------------------------
-- MISE À JOUR TABLE : marchand
-- ----------------------------

-- Ajout du PIN de connexion marchand
ALTER TABLE `marchand`
    ADD COLUMN `pin_marchand` varchar(10) NOT NULL AFTER `nom_boutique`;

-- Ajout du nom affiché sur le QR code
ALTER TABLE `marchand`
    ADD COLUMN `merchant_fixed_name` varchar(150) DEFAULT NULL AFTER `pin_marchand`;

-- Ajout de l'ID de paiement unique affiché sur le QR code
ALTER TABLE `marchand`
    ADD COLUMN `merchant_fixed_code` varchar(50) DEFAULT NULL AFTER `merchant_fixed_name`;

-- ----------------------------
-- MISE À JOUR TABLE : transaction
-- ----------------------------

-- Ajout de la référence unique (ex: WAV-123456)
ALTER TABLE `transaction`
    ADD COLUMN `transaction_ref` varchar(30) DEFAULT NULL AFTER `type_transaction`;

-- Ajout du nom du client pour l'historique marchand
ALTER TABLE `transaction`
    ADD COLUMN `client_name` varchar(100) DEFAULT NULL AFTER `transaction_ref`;
