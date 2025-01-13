<?php

class update
{
    public static function run(): void
    {
        global $db, $version, $db_database;

        $lastupdated = $db->getAll("SELECT value, name FROM settings WHERE name = 'lastupdated'");
        if (!isset($lastupdated[0])) {
            $db->execute("INSERT INTO settings SET name = 'lastupdated'");
        } else {
            $lastupdated = $lastupdated[0]['value'];
        }

        if ($lastupdated !== $version) {
            cache::clear();

            $needs162 = $db->getAll("SELECT value FROM settings WHERE name = 'enable_coupons'");
            if ($needs162 === null) {
                $db->execute("INSERT INTO settings SET name = ?, value2 = 1", [
                    'enable_coupons',
                ]);

                $db->execute("INSERT INTO settings SET name = ?, value2 = 0", [
                    'disable_tos',
                ]);

                $db->execute("CREATE TABLE coupons (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        coupon TEXT NOT NULL,
                        description TEXT NULL,
                        packages TEXT NOT NULL,
                        percent INT(11) NOT NULL,
                        uses INT(11) NOT NULL DEFAULT '0',
                        max_uses INT(11) NOT NULL DEFAULT '0',
                        expires TIMESTAMP NULL DEFAULT NULL,
                        timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    )
                    DEFAULT CHARACTER SET utf8
                    COLLATE utf8_general_ci
                ");
            }

            $needs163 = $db->getAll("SELECT * FROM postlogs");
            if ($needs163 === null) {
                $db->execute("CREATE TABLE IF NOT EXISTS postlogs (
                        log TEXT NULL,
                        timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                    )
                    DEFAULT CHARACTER SET utf8
                    COLLATE utf8_general_ci
                ");
            }

            try {
                $needs16312 = $db->getAll("SELECT * FROM actions WHERE expiretime = '0000-00-00'");
                if ($needs16312 !== null) {
                    $db->execute("
                    UPDATE actions SET expiretime = '1000-01-01' WHERE expiretime = '0000-00-00'
                ");

                    $db->execute("
                    ALTER TABLE actions MODIFY COLUMN expiretime datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
                ");

                    $db->execute("
                    UPDATE coupons SET expires = '1000-01-01' WHERE expires = '0000-00-00'
                ");

                    $db->execute("
                    ALTER TABLE coupons MODIFY COLUMN expires datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
                ");
                }
            } catch (Exception $e) {
                // do nothing, MySQL 8.0 does not support querying for 0000-00-00
            }

            try {
                $needs16314 = $db->getAll("SELECT * FROM players WHERE tos_lastread = '0000-00-00 00:00:00'");
                if ($needs16314 !== null) {
                    $db->execute("
                    UPDATE players SET tos_lastread = '1000-01-01' WHERE tos_lastread = '0000-00-00 00:00:00'
                ");

                    $db->execute("
                    ALTER TABLE players MODIFY COLUMN tos_lastread datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
                ");
                }
            } catch (Exception $e) {
                // do nothing, MySQL 8.0 does not support querying for 0000-00-00
            }

            $needs16315 = $db->getAll("
                SELECT *
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '$db_database'
                AND TABLE_NAME = 'players'
                AND COLUMN_NAME = 'created_at'
            ");
            if (count($needs16315) === 0) {
                $db->execute("
                    ALTER TABLE players ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER ip;
                ");

                $db->execute("
                    ALTER TABLE players ADD COLUMN email TEXT NULL DEFAULT NULL AFTER name;
                ");

                $db->execute("CREATE TABLE paymentwall_refids (
                        id INT NOT NULL,
                        ref VARCHAR(128) NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        UNIQUE INDEX ref (ref)
                    )
                    DEFAULT CHARACTER SET utf8   
                    COLLATE utf8_general_ci
                ");
            }

            $needs16320 = $db->getAll("SELECT * FROM settings WHERE name = 'halloween_things'");
            if (count($needs16320) === 0) {
                $db->execute("INSERT INTO settings SET name = 'halloween_things'");
            }

            $needs16321 = $db->getAll("SELECT * FROM settings WHERE name = 'disable_theme_selector'");
            if (count($needs16321) === 0) {
                $db->execute("INSERT INTO settings SET name = 'disable_theme_selector'");
                $db->execute("INSERT INTO settings SET name = 'disable_language_selector'");
            }

            $needs16323 = $db->getAll("SELECT * FROM pages WHERE page = 'privacy'");
            if (count($needs16323) === 0) {
                $db->execute("INSERT INTO pages SET page = 'privacy', content = '<p><strong>THIS IS AN EXAMPLE PRIVACY POLICY. REPLACE ANYTHING BETWEEN % AND % BEFORE USE. ALTERNATIVELY, USE YOUR OWN.</strong><br><br><br></p><p>This privacy policy governs the manner in which %COMMUNITY% collects, uses, maintains and discloses information collected from users (each, a \"User\") of the %HTTP://COMMUNITY.COM% website (\"Site\"). This privacy policy applies to the Site and all products and services offered by %COMMUNITY%.</p><br><p><strong>Personal identification information</strong><br>We may collect personal identification information from Users in a variety of ways, including, but not limited to, when Users visit our site, register on the site, place an order, and in connection with other activities, services, features or resources we make available on our Site. Users may visit our Site anonymously. We will collect personal identification information from Users only if they voluntarily submit such information to us. Users can always refuse to supply personally identification information, except that it may prevent them from engaging in certain Site related activities.</p><br><p>This Site uses the Steam Web APIs to retrieve data about users only when those users login using the Steam OpenID provider. The data we store from the Steam Web APIs include 64-bit Steam IDs, Steam Community names, and URLs to Steam Community avatar images.</p><br><p><strong>Web browser cookies</strong><br>Our Site may use \"cookies\" to enhance User experience. Users web browser places cookies on their hard drive for record-keeping purposes and sometimes to track information about them. User may choose to set their web browser to refuse cookies, or to alert you when cookies are being sent. If they do so, note that some parts of the Site may not function properly.</p><p><strong><br></strong></p><p><strong>How we use collected information</strong><br>%COMMUNITY% may collect and use Users personal information for the following purposes:</p><ol><li>To process payments: We may use the information Users provide about themselves when placing an order only to provide service to that order. We do not share this information with outside parties except to the extent necessary to provide the service.</li></ol><br><p><strong>Sharing your personal information</strong><br>We do not sell, trade, or rent Users personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates and advertisers for the purposes outlined above.</p><p><strong><br></strong></p><p><strong>Your acceptance of these terms</strong><br>By using this Site you signify your acceptance of this policy. If you do not agree to this policy, please do not use our Site. Your continued use of the Site following the posting of changes to this policy will be deemed your acceptance of those changes.</p><p><strong><br></strong></p><p><strong>Contacting us</strong><br>If you have any questions about this Privacy Policy, the practices of this site, or your dealings with this site, please contact us at: %PLACEHOLDER@EMAIL.COM%<br></p>'");

                $db->execute("INSERT INTO settings SET name = ?, value2 = ?", [
                    'privacy_enable', 0,
                ]);
            }

            $needs16325 = $db->getAll("
                SELECT *
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '$db_database'
                AND TABLE_NAME = 'packages'
                AND COLUMN_NAME = 'expire_linked'
            ");
            if (count($needs16325) === 0) {
                $db->execute("ALTER TABLE packages ADD expire_linked VARCHAR(1024) DEFAULT '[]' AFTER hide");
            }

            $needs164 = $db->getAll("
                SELECT *
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '$db_database'
                AND TABLE_NAME = 'advent_calendar'
                AND COLUMN_NAME = 'random'
            ");
            if (count($needs164) === 0) {
                $db->execute("ALTER TABLE advent_calendar ADD credits int DEFAULT null NULL");
                $db->execute("ALTER TABLE advent_calendar ADD random tinyint DEFAULT null NULL");
            }

            $needs1641 = $db->getAll("SELECT random FROM advent_calendar WHERE random IS NULL");
            if (count($needs1641) === 0) {
                $db->execute("UPDATE advent_calendar SET random = 33 WHERE random = 1");
                $db->execute("UPDATE advent_calendar SET random = null WHERE random = 0");
            }

            $needs1642 = $db->getAll("SELECT * FROM settings WHERE name = 'advent_claim_current_day_only'");
            if (count($needs1642) === 0) {
                $db->execute("INSERT INTO settings SET name = 'advent_claim_current_day_only', value2 = 0");
                $db->execute("INSERT INTO settings SET name = 'store_disable_read_more', value2 = 0");
            }

            $needs175 = $db->getAll("SHOW INDEX FROM players WHERE Key_name = 'players_uid_idx'");
            if (count($needs175) === 0) {
                $db->execute("ALTER TABLE `players` ADD INDEX `players_uid_idx` (`uid`)");
                $db->execute("ALTER TABLE `transactions` ADD INDEX `transactions_price_id_idx` (`price`, `id`)");
                $db->execute("ALTER TABLE `transactions` ADD INDEX `transactions_uid_idx` (`uid`)");
                $db->execute("ALTER TABLE `settings` ADD INDEX `settings_name_idx` (`name`)");
                $db->execute("ALTER TABLE `actions` ADD INDEX `actions_uid_idx` (`uid`)");
            }

            $needs193 = $db->getAll("
                SELECT *
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '$db_database'
                AND TABLE_NAME = 'stripe_customers'
            ");
            if (count($needs193) === 0) {
                $db->execute("CREATE TABLE stripe_customers (
                        uid BIGINT(20) NOT NULL,
                        customer_id varchar(128) NOT NULL
                    )
                    DEFAULT CHARACTER SET utf8mb4
                    COLLATE utf8mb4_general_ci
                ");

                $db->execute("create unique index stripe_customers_uid_customer_id_uindex on stripe_customers (uid, customer_id);");
            }

            setSetting($version, 'lastupdated', 'value');
        }
    }
}
