-- MS SQL Server Schema for Rocket Production Management System
CREATE TABLE Users (
    UserID INT IDENTITY PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL,
    FullName NVARCHAR(100)
);

CREATE TABLE Projects (
    ProjectID INT IDENTITY PRIMARY KEY,
    ProjectName NVARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Models (
    ModelID INT IDENTITY PRIMARY KEY,
    ProjectID INT NOT NULL,
    ModelName NVARCHAR(100) NOT NULL,
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
    LinerUsageID INT IDENTITY PRIMARY KEY,
    ProductionNumber VARCHAR(50) NOT NULL,
    LinerType VARCHAR(50) NOT NULL,
    LinerBatchNumber VARCHAR(50) NOT NULL,
    Remarks NVARCHAR(255) NULL,
    CONSTRAINT FK_LinerUsage_ProdOrders FOREIGN KEY(ProductionNumber)
        REFERENCES ProductionOrders(ProductionNumber)
);

CREATE TABLE MC02_ProcessLog (
    LogID INT IDENTITY PRIMARY KEY,
    ProductionNumber VARCHAR(50) NOT NULL,
    SequenceNo INT NOT NULL,
    ProcessStepName NVARCHAR(200) NOT NULL,
    DatePerformed DATE NULL,
    Result VARCHAR(20) NULL,
    Operator_UserID INT NULL,
    Remarks NVARCHAR(MAX) NULL,
    ControlValue DECIMAL(10,3) NULL,
    ActualMeasuredValue DECIMAL(10,3) NULL,
    CONSTRAINT FK_ProcessLog_ProdOrders FOREIGN KEY(ProductionNumber)
        REFERENCES ProductionOrders(ProductionNumber),
    CONSTRAINT FK_ProcessLog_Users FOREIGN KEY(Operator_UserID)
        REFERENCES Users(UserID)
);
GO

