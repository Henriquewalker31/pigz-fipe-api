<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema inicial: users, api_token, fipe_model, fipe_price, vehicle';
    }

    public function up(Schema $schema): void
    {
        $o = 'DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB';

        $this->addSql("CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(120) NOT NULL, role VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_users_email (email), PRIMARY KEY(id)) $o");
        $this->addSql("CREATE TABLE api_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_api_token (token), INDEX idx_api_token_user (user_id), PRIMARY KEY(id)) $o");
        $this->addSql("CREATE TABLE fipe_model (id INT AUTO_INCREMENT NOT NULL, fipe_code VARCHAR(10) NOT NULL, vehicle_type VARCHAR(20) NOT NULL, brand VARCHAR(80) NOT NULL, model VARCHAR(150) NOT NULL, model_year INT NOT NULL, fuel VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_fipe_code_year_fuel (fipe_code, model_year, fuel), INDEX idx_fipe_brand (brand), PRIMARY KEY(id)) $o");
        $this->addSql("CREATE TABLE fipe_price (id INT AUTO_INCREMENT NOT NULL, fipe_model_id INT NOT NULL, reference_month DATE NOT NULL, price NUMERIC(12, 2) NOT NULL, UNIQUE INDEX uniq_fipe_price_month (fipe_model_id, reference_month), PRIMARY KEY(id)) $o");
        $this->addSql("CREATE TABLE vehicle (id INT AUTO_INCREMENT NOT NULL, fipe_model_id INT DEFAULT NULL, brand VARCHAR(80) NOT NULL, model VARCHAR(150) NOT NULL, version VARCHAR(150) DEFAULT NULL, body_type VARCHAR(30) DEFAULT NULL, manufacture_year INT NOT NULL, model_year INT NOT NULL, fuel VARCHAR(30) NOT NULL, transmission VARCHAR(30) DEFAULT NULL, color VARCHAR(40) DEFAULT NULL, mileage INT NOT NULL, doors INT DEFAULT NULL, features LONGTEXT DEFAULT NULL, city VARCHAR(80) DEFAULT NULL, state VARCHAR(2) DEFAULT NULL, price NUMERIC(12, 2) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_vehicle_fipe (fipe_model_id), INDEX idx_vehicle_status (status), INDEX idx_vehicle_price (price), PRIMARY KEY(id)) $o");

        $this->addSql('ALTER TABLE api_token ADD CONSTRAINT fk_api_token_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fipe_price ADD CONSTRAINT fk_fipe_price_model FOREIGN KEY (fipe_model_id) REFERENCES fipe_model (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT fk_vehicle_fipe_model FOREIGN KEY (fipe_model_id) REFERENCES fipe_model (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vehicle');
        $this->addSql('DROP TABLE fipe_price');
        $this->addSql('DROP TABLE fipe_model');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('DROP TABLE users');
    }
}