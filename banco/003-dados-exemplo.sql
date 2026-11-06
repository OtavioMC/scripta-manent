SET NAMES utf8mb4;

INSERT INTO Autor (CodAu, Nome) VALUES
  (1, 'Machado de Assis'),
  (2, 'Clarice Lispector'),
  (3, 'Graciliano Ramos'),
  (4, 'Carolina Maria de Jesus'),
  (5, 'Neil Gaiman'),
  (6, 'Terry Pratchett'),
  (7, 'Steven D. Levitt'),
  (8, 'Stephen J. Dubner'),
  (9, 'Erich Gamma'),
  (10, 'Richard Helm'),
  (11, 'Ralph Johnson'),
  (12, 'John Vlissides'),
  (13, 'Lygia Fagundes Telles');

INSERT INTO Assunto (codAs, Descricao) VALUES
  (1, 'Romance'),
  (2, 'Clássico'),
  (3, 'Conto'),
  (4, 'Humor'),
  (5, 'Fantasia'),
  (6, 'Economia'),
  (7, 'Programação'),
  (8, 'Software'),
  (9, 'Poesia');

INSERT INTO Livro (Codl, Titulo, Editora, Edicao, AnoPublicacao, Valor) VALUES
  (1, 'Dom Casmurro', 'Penguin-Companhia', 1, '2016', 34.90),
  (2, 'Memórias Póstumas de Brás Cubas', 'Companhia das Letras', 1, '2014', 42.00),
  (3, 'A Hora da Estrela', 'Rocco', 1, '2020', 39.90),
  (4, 'Laços de Família', 'Rocco', 2, '2009', 44.90),
  (5, 'Vidas Secas', 'Record', 3, '2019', 36.50),
  (6, 'Quarto de Despejo', 'Ática', 10, '2014', 32.00),
  (7, 'Coraline', 'Intrínseca', 1, '2020', 39.90),
  (8, 'Belas Maldições', 'Bertrand Brasil', 1, '2019', 59.90),
  (9, 'Freakonomics', 'Elsevier', 1, '2007', 64.00),
  (10, 'SuperFreakonomics', 'Elsevier', 1, '2010', 69.00),
  (11, 'Padrões de Projeto', 'Bookman', 1, '2000', 189.00),
  (12, 'As Mil e Uma Noites', 'Globo', 1, '2017', 79.90);

INSERT INTO Livro_Autor (Livro_Codl, Autor_CodAu) VALUES
  (1, 1),
  (2, 1),
  (3, 2),
  (4, 2),
  (5, 3),
  (6, 4),
  (7, 5),
  (8, 5),
  (8, 6),
  (9, 7),
  (9, 8),
  (10, 7),
  (10, 8),
  (11, 9),
  (11, 10),
  (11, 11),
  (11, 12);

INSERT INTO Livro_Assunto (Livro_Codl, Assunto_codAs) VALUES
  (1, 1),
  (1, 2),
  (2, 1),
  (2, 2),
  (2, 4),
  (3, 1),
  (4, 3),
  (5, 1),
  (5, 2),
  (7, 5),
  (8, 4),
  (8, 5),
  (9, 6),
  (10, 6),
  (11, 7),
  (11, 8),
  (12, 3),
  (12, 5);
