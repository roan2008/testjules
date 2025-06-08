-- MySQL Schema for Rocket Production Management System
CREATE DATABASE IF NOT EXISTS rocketprod;
USE rocketprod;

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
    Remarks TEXT NULL,
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

-- New tables for Process Templates (Phase 4)
CREATE TABLE ProcessTemplates (
    TemplateID INT AUTO_INCREMENT PRIMARY KEY,
    TemplateName VARCHAR(100) NOT NULL,
    ProjectID INT NULL,
    ModelID INT NULL,
    IsDefault BOOLEAN DEFAULT FALSE,
    CreatedBy INT NOT NULL,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT FK_Templates_Projects FOREIGN KEY(ProjectID)
        REFERENCES Projects(ProjectID),
    CONSTRAINT FK_Templates_Models FOREIGN KEY(ModelID)
        REFERENCES Models(ModelID),
    CONSTRAINT FK_Templates_Users FOREIGN KEY(CreatedBy)
        REFERENCES Users(UserID)
);

CREATE TABLE ProcessTemplateSteps (
    TemplateStepID INT AUTO_INCREMENT PRIMARY KEY,
    TemplateID INT NOT NULL,
    ProcessName VARCHAR(100) NOT NULL,
    StepOrder INT NOT NULL,
    IsRequired BOOLEAN DEFAULT TRUE,
    EstimatedDuration INT NULL,
    CONSTRAINT FK_TemplateSteps_Templates FOREIGN KEY(TemplateID)
        REFERENCES ProcessTemplates(TemplateID)
);

-- Insert sample data
INSERT INTO Users (Username, PasswordHash, Role, FullName) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'Admin User'),
('operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator', 'John Operator');

INSERT INTO Projects (ProjectName) VALUES 
('Apollo Mission'), ('Mars Explorer');

INSERT INTO Models (ProjectID, ModelName) VALUES 
(1, 'Apollo-V1'), (1, 'Apollo-V2'), (2, 'Mars-Rover-1');

INSERT INTO ProductionOrders (ProductionNumber, EmptyTubeNumber, ProjectID, ModelID, MC02_Status) VALUES 
('PO-2025-001', 'TUBE-001', 1, 1, 'Pending'),
('PO-2025-002', 'TUBE-002', 1, 2, 'In Progress'),
('PO-2025-003', 'TUBE-003', 2, 3, 'Completed');
