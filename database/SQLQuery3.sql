INSERT INTO Utilizatori (Nume, Prenume, Email, Telefon, Rol, Parola, DataReg) VALUES
('Popescu', 'Andrei', 'andrei.popescu@email.com', '0712345678', 'client', 'parola1', '2025-01-01'),
('Ionescu', 'Maria', 'maria.ionescu@email.com', '0723456789', 'client', 'parola2', '2025-02-15'),
('Georgescu', 'Ion', 'ion.georgescu@email.com', '0734567890', 'administrator', 'parola3', '2025-03-10'),
('Dumitrescu', 'Elena', 'elena.dumitrescu@email.com', '0745678901', 'client', 'parola4', '2025-04-05'),
('Stan', 'Adrian', 'adrian.stan@email.com', '0756789012', 'administrator', 'parola5', '2025-05-20'),
('Marin', 'Ioana', 'ioana.marin@email.com', '0767890123', 'client', 'parola6', '2025-06-15');

INSERT INTO Abonamente (Tip, Pret, Ore, Descriere) VALUES
('Basic', 50.00, 10, 'Acces limitat la resurse'),
('Standard', 100.00, 20, 'Acces mediu la resurse si sali'),
('Premium', 200.00, 40, 'Acces complet si facilitati extra'),
('Student', 30.00, 8, 'Oferta speciala pentru studenti'),
('Business', 300.00, 50, 'Pachet complet pentru companii'),
('Weekend', 40.00, 12, 'Acces doar in weekend');

INSERT INTO AbonamenteClient (IdClient, IdAbonament, DataStart, DataEnd) VALUES
(1, 1, '2025-01-05', '2025-02-05'),
(2, 2, '2025-02-20', '2025-03-20'),
(4, 3, '2025-04-10', '2025-05-10'),
(6, 1, '2025-06-01', '2025-07-01'),
(1, 4, '2025-01-15', '2025-02-15'),
(5, 5, '2025-05-25', '2025-06-25');

INSERT INTO Spatii (Denumire, IdAdmin, Strada, Numar, Oras, Judet, Descriere) VALUES
('Spatiu Central', 3, 'Str. Libertatii', '10', 'Bucuresti', 'Bucuresti', 'Spatiu central pentru coworking'),
('Spatiu Nord', 5, 'Str. Nordului', '15', 'Cluj-Napoca', 'Cluj', 'Spatiu modern pentru intalniri'),
('Spatiu Est', 3, 'Str. Estului', '7', 'Iasi', 'Iasi', 'Spatiu coworking si birouri private'),
('Spatiu Vest', 5, 'Str. Vestului', '22', 'Timisoara', 'Timis', 'Spatiu pentru intalniri si birouri'),
('Spatiu Sud', 3, 'Str. Sudului', '5', 'Craiova', 'Dolj', 'Spatiu open space si birouri'),
('Spatiu Central 2', 5, 'Str. Piatra', '12', 'Brasov', 'Brasov', 'Spatiu multifunctional');

INSERT INTO Sali (IdSpatiu, Denumire, Tip, Capacitate, PretOra) VALUES
(1, 'Sala A', 'open space', 20, 50.00),
(1, 'Sala B', 'sala meeting', 10, 30.00),
(2, 'Sala C', 'birou privat', 5, 25.00),
(3, 'Sala D', 'open space', 15, 40.00),
(4, 'Sala E', 'sala meeting', 12, 35.00),
(5, 'Sala F', 'birou privat', 6, 20.00);

INSERT INTO Birouri (IdSala, Cod, PretOra) VALUES
(1, 'B1', 15.00),
(1, 'B2', 20.00),
(2, 'B3', 25.00),
(3, 'B4', 30.00),
(4, 'B5', 18.00),
(5, 'B6', 22.00);

INSERT INTO Rezervari (IdAbonament, DataStart, DataEnd, Status) VALUES
(1, '2025-01-10 09:00', '2025-01-10 12:00', 'asteptare'),
(2, '2025-02-22 10:00', '2025-02-22 13:00', 'confirmata'),
(3, '2025-04-15 14:00', '2025-04-15 16:00', 'anulata'),
(4, '2025-01-20 09:00', '2025-01-20 11:00', 'confirmata'),
(5, '2025-06-01 10:00', '2025-06-01 12:00', 'asteptare'),
(6, '2025-06-05 13:00', '2025-06-05 15:00', 'confirmata');

INSERT INTO RezervareBirou (IdRezervare, IdBirou) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6);

INSERT INTO Plati (IdRezervare, Suma, Data, Metoda, Status) VALUES
(1, 50.00, '2025-01-10', 'card', 'efectuata'),
(2, 30.00, '2025-02-22', 'numerar', 'asteptare'),
(3, 40.00, '2025-04-15', 'transfer', 'respinsa'),
(4, 25.00, '2025-01-20', 'card', 'efectuata'),
(5, 35.00, '2025-06-01', 'numerar', 'asteptare'),
(6, 45.00, '2025-06-05', 'transfer', 'efectuata');

INSERT INTO Facilitati (Denumire, Descriere) VALUES
('WiFi', 'Acces internet rapid'),
('Bucatarie', 'Bucatarie complet utilata'),
('Parcare', 'Parcare gratuita'),
('Proiector', 'Proiector si ecran'),
('Aer conditionat', 'Aer conditionat in toate incaperile'),
('Print/Scan', 'Servicii de printare si scanare');

INSERT INTO SpatiuFacilitate (IdSpatiu, IdFacilitate) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 4),
(3, 5),
(4, 6);
