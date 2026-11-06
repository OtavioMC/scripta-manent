<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Pdf;

use CURLStringFile;
use ScriptaManent\Application\Relatorio\GeradorDePdf;
use ScriptaManent\Application\Relatorio\PdfIndisponivel;

final readonly class GeradorDePdfGotenberg implements GeradorDePdf
{
    private const string ROTA = '/forms/chromium/convert/html';

    public function __construct(
        private string $endereco,
        private int $tempoLimiteEmSegundos,
    ) {
    }

    public function ligado(): bool
    {
        return $this->endereco !== '';
    }

    public function gerar(string $html, array $arquivos): string
    {
        if (!$this->ligado()) {
            throw new PdfIndisponivel('A geração de PDF está desligada: GOTENBERG_URL está vazio.');
        }

        $campos = [
            'files[0]' => new CURLStringFile($html, 'index.html', 'text/html'),
            'paperWidth' => '8.27',
            'paperHeight' => '11.7',
            'marginTop' => '0.6',
            'marginBottom' => '0.6',
            'marginLeft' => '0.5',
            'marginRight' => '0.5',
            'printBackground' => 'true',
        ];
        $indice = 1;
        foreach ($arquivos as $nome => $conteudo) {
            $campos[sprintf('files[%d]', $indice++)] = new CURLStringFile($conteudo, $nome, 'text/css');
        }

        $requisicao = curl_init(rtrim($this->endereco, '/') . self::ROTA);
        curl_setopt_array($requisicao, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $campos,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $this->tempoLimiteEmSegundos,
        ]);
        $resposta = curl_exec($requisicao);
        $status = curl_getinfo($requisicao, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($requisicao);

        if (!is_string($resposta) || $status !== 200) {
            throw new PdfIndisponivel(sprintf(
                'O Gotenberg não gerou o PDF (status %d%s).',
                $status,
                $erro === '' ? '' : ', ' . $erro,
            ));
        }

        return $resposta;
    }
}
