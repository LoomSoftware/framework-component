DROP SCHEMA IF EXISTS Application;

CREATE SCHEMA Application;

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
    PRIMARY KEY (intPackageId),
    FOREIGN KEY (intPackageTypeId) REFERENCES ublPackageType(intPackageTypeId)
);

INSERT INTO tblPackage
    (strPackageName, intPackageTypeId)
VALUES
    ('Package A', @packageTypeA),
    ('Package B', @packageTypeA),
    ('Package C', @packageTypeA),
    ('Package D', @packageTypeB),
    ('Package E', @packageTypeB),
    ('Package F', @packageTypeB),
    ('Package G', @packageTypeB);