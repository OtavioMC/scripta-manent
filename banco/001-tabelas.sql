CREATE TABLE Livro (
  Codl INTEGER NOT NULL,
  Titulo VARCHAR(40) NOT NULL,
  Editora VARCHAR(40) NOT NULL,
  Edicao INTEGER NOT NULL,
  AnoPublicacao VARCHAR(4) NOT NULL,
  Valor DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (Codl)
);

CREATE TABLE Autor (
  CodAu INTEGER NOT NULL,
  Nome VARCHAR(40) NOT NULL,
  PRIMARY KEY (CodAu)
);

CREATE TABLE Assunto (
  codAs INTEGER NOT NULL,
  Descricao VARCHAR(20) NOT NULL,
  PRIMARY KEY (codAs)
);

CREATE TABLE Livro_Autor (
  Livro_Codl INTEGER NOT NULL,
  Autor_CodAu INTEGER NOT NULL,
  PRIMARY KEY (Livro_Codl, Autor_CodAu)
);

CREATE TABLE Livro_Assunto (
  Livro_Codl INTEGER NOT NULL,
  Assunto_codAs INTEGER NOT NULL,
  PRIMARY KEY (Livro_Codl, Assunto_codAs)
);
