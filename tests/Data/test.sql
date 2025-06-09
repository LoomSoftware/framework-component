DROP SCHEMA IF EXISTS Application;
DROP SCHEMA IF EXISTS Security;

CREATE SCHEMA Application;
CREATE SCHEMA Security;

USE Security;

CREATE TABLE ublRole (
    intRoleId INT UNSIGNED NOT NULL AUTO_INCREMENT,
    strRoleName VARCHAR(60) NOT NULL,
    strRoleHandle VARCHAR(60) NOT NULL,
    PRIMARY KEY (intRoleId),
    UNIQUE (strRoleHandle)
);

INSERT INTO ublRole
    (strRoleName, strRoleHandle)
VALUES
    ('User', 'USER');

CREATE TABLE tblUser (
    intUserId INT UNSIGNED NOT NULL AUTO_INCREMENT,
    strUsername VARCHAR(60) NOT NULL,
    strEmail VARCHAR(255) NOT NULL,
    intRoleId INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (intUserId),
    UNIQUE (strUsername),
    FOREIGN KEY (intRoleId) REFERENCES ublRole(intRoleId)
);

INSERT INTO tblUser
    (strUsername, strEmail)
VALUES
    ('Test User', 'testuser@test.com');

USE Application;

CREATE TABLE ublPackageType (
    intPackageTypeId INT UNSIGNED NOT NULL AUTO_INCREMENT,
    strPackageTypeName VARCHAR(255) NOT NULL,
    PRIMARY KEY (intPackageTypeId)
);

INSERT INTO ublPackageType
    (strPackageTypeName)
VALUES
    ('Package Type A'),
    ('Package Type B'),
    ('Package Type C');

SET @packageTypeA = (SELECT intPackageTypeId FROM ublPackageType WHERE strPackageTypeName = 'Package Type A');
SET @packageTypeB = (SELECT intPackageTypeId FROM ublPackageType WHERE strPackageTypeName = 'Package Type B');
SET @packageTypeC = (SELECT intPackageTypeId FROM ublPackageType WHERE strPackageTypeName = 'Package Type C');

CREATE TABLE tblPackage (
    intPackageId INT UNSIGNED NOT NULL AUTO_INCREMENT,
    strPackageName VARCHAR(255) NOT NULL,
    intPackageTypeId INT UNSIGNED NOT NULL,
    intOwnerId INT UNSIGNED NOT NULL,
    PRIMARY KEY (intPackageId),
    FOREIGN KEY (intPackageTypeId) REFERENCES ublPackageType(intPackageTypeId),
    FOREIGN KEY (intOwnerId) REFERENCES Security.tblUser(intUserId)
);

INSERT INTO tblPackage
    (strPackageName, intPackageTypeId, intOwnerId)
VALUES
    ('Package A', @packageTypeA, 1),
    ('Package B', @packageTypeA, 1),
    ('Package C', @packageTypeA, 1),
    ('Package D', @packageTypeB, 1),
    ('Package E', @packageTypeB, 1),
    ('Package F', @packageTypeB, 1),
    ('Package G', @packageTypeB, 1);