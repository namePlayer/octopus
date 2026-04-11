-- db.Account definition

CREATE TABLE db.Account (
    id INT UNSIGNED auto_increment NOT NULL,
    uuid CHAR(36) NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    firstname varchar(100) NOT NULL,
    lastname varchar(100) NOT NULL,
    registeredAt DATETIME NOT NULL,
    passwordResetToken VARCHAR(64) NULL,
    passwordResetTokenExpires DATETIME NULL,
    CONSTRAINT Account_PK PRIMARY KEY (id),
    CONSTRAINT Account_UNIQUE UNIQUE KEY (uuid),
    CONSTRAINT Account_UNIQUE_1 UNIQUE KEY (email),
    CONSTRAINT Account_UNIQUE_TOKEN UNIQUE KEY (passwordResetToken)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
