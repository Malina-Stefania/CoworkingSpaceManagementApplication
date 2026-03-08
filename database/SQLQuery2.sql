CREATE TABLE Utilizatori (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Nume NVARCHAR(100) NOT NULL,
    Prenume NVARCHAR(100) NOT NULL,
    Email NVARCHAR(150) NOT NULL UNIQUE,
    Telefon NVARCHAR(20),
    Rol NVARCHAR(20) NOT NULL,
    Parola NVARCHAR(255) NOT NULL,
    DataReg DATE NOT NULL,
	CONSTRAINT CHK_Utilizatori_Rol CHECK (Rol IN ('client','administrator'))
);

CREATE TABLE Abonamente (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Tip NVARCHAR(50) NOT NULL,
    Pret DECIMAL(10,2) NOT NULL,
    Ore INT NOT NULL,
    Descriere NVARCHAR(MAX),
	CONSTRAINT CHK_Abonamente_Pret CHECK (Pret >= 0),
    CONSTRAINT CHK_Abonamente_Ore CHECK (Ore >= 0)
);

CREATE TABLE AbonamenteClient (
    IdClient INT NOT NULL,
    IdAbonament INT NOT NULL,
    DataStart DATE NOT NULL,
    DataEnd DATE not null,
    PRIMARY KEY (IdClient, IdAbonament),
    FOREIGN KEY (IdClient) REFERENCES Utilizatori(Id) ON DELETE CASCADE,
    FOREIGN KEY (IdAbonament) REFERENCES Abonamente(Id) ON DELETE CASCADE,
	CHECK (DataEnd > DataStart)
);

CREATE TABLE Spatii (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Denumire NVARCHAR(150) NOT NULL,
    IdAdmin INT NOT NULL,
    Strada NVARCHAR(150),
    Numar NVARCHAR(10),
    Oras NVARCHAR(100),
    Judet NVARCHAR(100),
    Descriere NVARCHAR(MAX),
    FOREIGN KEY (IdAdmin) REFERENCES Utilizatori(Id)
        ON UPDATE CASCADE
        ON DELETE NO ACTION
);

CREATE TABLE Sali (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    IdSpatiu INT NOT NULL,
    Denumire NVARCHAR(150) NOT NULL,
    Tip NVARCHAR(50) NOT NULL,
    Capacitate INT NOT NULL,
    PretOra DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (IdSpatiu) REFERENCES Spatii(Id) ON DELETE CASCADE,
	CONSTRAINT CHK_Sali_Tip CHECK (Tip IN ('open space','sala meeting','birou privat')),
    CONSTRAINT CHK_Sali_Capac CHECK (Capacitate > 0),
    CONSTRAINT CHK_Sali_Pret CHECK (PretOra >= 0),
);

CREATE TABLE Birouri (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    IdSala INT NOT NULL,
    Cod NVARCHAR(50) NOT NULL,
    PretOra DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (IdSala) REFERENCES Sali(Id) ON DELETE CASCADE,
	CONSTRAINT CHK_Birouri_Pret CHECK (PretOra >= 0),
);

CREATE TABLE Rezervari (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    IdAbonament INT,
    DataStart DATETIME NOT NULL,
    DataEnd DATETIME NOT NULL,
    Status NVARCHAR(20) NOT NULL,
    FOREIGN KEY (IdAbonament) REFERENCES Abonamente(Id) ON DELETE SET NULL,
    CONSTRAINT CHK_Rezervari_Status CHECK (DataEnd > DataStart),
	CONSTRAINT CHK_Rezervari_Interval CHECK (Status IN ('asteptare','confirmata','anulata'))
);

CREATE TABLE RezervareBirou (
    IdRezervare INT NOT NULL,
    IdBirou INT NOT NULL,
    PRIMARY KEY (IdRezervare, IdBirou),
    FOREIGN KEY (IdRezervare) REFERENCES Rezervari(Id) ON DELETE CASCADE,
    FOREIGN KEY (IdBirou) REFERENCES Birouri(Id) ON DELETE CASCADE
);

CREATE TABLE Plati (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    IdRezervare INT NOT NULL,
    Suma DECIMAL(10,2) NOT NULL,
    Data DATE NOT NULL,
    Metoda NVARCHAR(20) NOT NULL,
    Status NVARCHAR(20) NOT NULL,
    FOREIGN KEY (IdRezervare) REFERENCES Rezervari(Id) ON DELETE CASCADE,
	CONSTRAINT CHK_Plati_Suma CHECK (Suma >= 0),
	CONSTRAINT CHK_Plati_Metoda CHECK (Metoda IN ('card', 'numerar', 'transfer')),
	CONSTRAINT CHK_Plati_Status CHECK (Status IN ('efectuata', 'asteptare', 'respinsa'))
);

CREATE TABLE Facilitati (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Denumire NVARCHAR(100) NOT NULL,
    Descriere NVARCHAR(MAX)
);

CREATE TABLE SpatiuFacilitate (
    IdSpatiu INT NOT NULL,
    IdFacilitate INT NOT NULL,
    PRIMARY KEY (IdSpatiu, IdFacilitate),
    FOREIGN KEY (IdSpatiu) REFERENCES Spatii(Id) ON DELETE CASCADE,
    FOREIGN KEY (IdFacilitate) REFERENCES Facilitati(Id) ON DELETE CASCADE
);