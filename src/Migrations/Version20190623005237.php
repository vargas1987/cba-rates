<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190623005237 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("CREATE SCHEMA billing;");
        $this->addSql("
            CREATE TABLE billing.currency
            (
                char_code TEXT PRIMARY KEY NOT NULL,
                num_code INTEGER NOT NULL UNIQUE,
                name TEXT NOT NULL UNIQUE
            );
        ");
        $this->addSql("CREATE INDEX billing_currency_num_code_index ON billing.currency (num_code);");
        $this->addSql("CREATE INDEX billing_currency_name_index ON billing.currency (name);");
        $this->addSql("
            CREATE TABLE billing.currency_rate
            (
                id SERIAL PRIMARY KEY,
                date DATE NOT NULL,
                currency_from TEXT NOT NULL,
                currency_to TEXT NOT NULL,
                nominal INTEGER NOT NULL,
                value NUMERIC (15,4) NOT NULL
            );
        ");
        $this->addSql("CREATE INDEX billing_currency_rate_currency_from_index ON billing.currency_rate (currency_from);");
        $this->addSql("CREATE INDEX billing_currency_rate_currency_to_index ON billing.currency_rate (currency_to);");
        $this->addSql("CREATE UNIQUE INDEX billing_currency_rate_index ON billing.currency_rate (date, currency_from, currency_to);");
        $this->addSql("ALTER TABLE billing.currency_rate ADD FOREIGN KEY (currency_from) REFERENCES billing.currency (char_code);");
        $this->addSql("ALTER TABLE billing.currency_rate ADD FOREIGN KEY (currency_to) REFERENCES billing.currency (char_code);");

    }

    public function down(Schema $schema) : void
    {
        $this->addSql("DROP TABLE billing.currency_rate;");
        $this->addSql("DROP TABLE billing.currency;");
        $this->addSql("DROP SCHEMA billing;");
    }
}
