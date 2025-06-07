-- MS SQL Server Schema for Rocket Production Management System
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL,
    FullName VARCHAR(100)
);

CREATE TABLE Projects (
    ProjectID INT AUTO_INCREMENT PRIMARY KEY,
    ProjectName VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Models (
    ModelID INT AUTO_INCREMENT PRIMARY KEY,
    ProjectID INT NOT NULL,
    ModelName VARCHAR(100) NOT NULL,
    CONSTRAINT FK_Models_Projects FOREIGN KEY(ProjectID)
        REFERENCES Projects(ProjectID)
);

CREATE TABLE ProductionOrders (
    ProductionNumber VARCHAR(50) PRIMARY KEY,
    EmptyTubeNumber VARCHAR(50),
    ProjectID INT NOT NULL,
    ModelID INT NOT NULL,
    MC02_Status VARCHAR(30) DEFAULT 'Pending',
    MC02_SignedBy_UserID INT NULL,
    MC02_SignedDate DATETIME NULL,
    CONSTRAINT FK_ProdOrders_Projects FOREIGN KEY(ProjectID)
        REFERENCES Projects(ProjectID),
    CONSTRAINT FK_ProdOrders_Models FOREIGN KEY(ModelID)
        REFERENCES Models(ModelID),
    CONSTRAINT FK_ProdOrders_Users FOREIGN KEY(MC02_SignedBy_UserID)
        REFERENCES Users(UserID)
);

CREATE TABLE MC02_LinerUsage (
    LinerUsageID INT AUTO_INCREMENT PRIMARY KEY,
    ProductionNumber VARCHAR(50) NOT NULL,
    LinerType VARCHAR(50) NOT NULL,
    LinerBatchNumber VARCHAR(50) NOT NULL,
    Remarks VARCHAR(255) NULL,
    CONSTRAINT FK_LinerUsage_ProdOrders FOREIGN KEY(ProductionNumber)
        REFERENCES ProductionOrders(ProductionNumber)
);


CREATE TABLE MC02_ProcessLog (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    ProductionNumber VARCHAR(50) NOT NULL,
    SequenceNo INT NOT NULL,
    ProcessStepName VARCHAR(200) NOT NULL,
    DatePerformed DATE NULL,
    Result VARCHAR(20) NULL,
    Operator_UserID INT NULL,
    Remarks TEXT NULL,
    ControlValue DECIMAL(10,3) NULL,
    ActualMeasuredValue DECIMAL(10,3) NULL,
    CONSTRAINT FK_ProcessLog_ProdOrders FOREIGN KEY(ProductionNumber)
        REFERENCES ProductionOrders(ProductionNumber),
    CONSTRAINT FK_ProcessLog_Users FOREIGN KEY(Operator_UserID)
        REFERENCES Users(UserID)
);


