CREATE SQL SECURITY INVOKER VIEW vw_livros_por_autor AS
SELECT
  Autor.CodAu,
  Autor.Nome AS Autor,
  Livro.Codl,
  Livro.Titulo,
  Livro.Editora,
  Livro.Edicao,
  Livro.AnoPublicacao,
  Livro.Valor,
  AssuntosDoLivro.Assuntos
FROM Livro
JOIN Livro_Autor ON Livro_Autor.Livro_Codl = Livro.Codl
JOIN Autor ON Autor.CodAu = Livro_Autor.Autor_CodAu
LEFT JOIN (
  SELECT
    Livro_Assunto.Livro_Codl,
    GROUP_CONCAT(Assunto.Descricao ORDER BY Assunto.Descricao SEPARATOR ', ') AS Assuntos
  FROM Livro_Assunto
  JOIN Assunto ON Assunto.codAs = Livro_Assunto.Assunto_codAs
  GROUP BY Livro_Assunto.Livro_Codl
) AS AssuntosDoLivro ON AssuntosDoLivro.Livro_Codl = Livro.Codl;
