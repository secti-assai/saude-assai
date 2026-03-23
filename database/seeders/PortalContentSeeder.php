<?php

namespace Database\Seeders;

use App\Models\PortalContent;
use Illuminate\Database\Seeder;

class PortalContentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'type' => 'ALERTA',
                'title' => 'Alerta respiratorio: reforco de cuidados para idosos e criancas',
                'body' => 'A Secretaria Municipal de Saude orienta o uso de mascara em casos sintomaticos, hidratacao constante e busca imediata por atendimento em casos de falta de ar.',
            ],
            [
                'type' => 'NOTICIA',
                'title' => 'Nova sala de triagem digital entra em operacao na UBS Central',
                'body' => 'Com equipamentos de monitoramento em tempo real, a unidade amplia a velocidade no acolhimento e melhora o direcionamento clinico dos pacientes.',
            ],
            [
                'type' => 'NOTICIA',
                'title' => 'Campanha de vacinacao itinerante inicia nos bairros da zona rural',
                'body' => 'Acoes com equipe movel para ampliar cobertura vacinal e facilitar o acesso da populacao aos imunizantes de rotina.',
            ],
            [
                'type' => 'AVISO',
                'title' => 'Horario estendido da Farmacia Municipal nesta semana',
                'body' => 'Atendimento ate as 20h para retirada de medicamentos de uso continuo mediante apresentacao de receita valida e documento oficial.',
            ],
            [
                'type' => 'CAMPANHA',
                'title' => 'Mutirao de prevencao ao diabetes com testes rapidos gratuitos',
                'body' => 'Realizacao de exames de glicemia, orientacoes nutricionais e encaminhamento para acompanhamento multiprofissional.',
            ],
        ];

        foreach ($items as $index => $item) {
            PortalContent::updateOrCreate(
                ['title' => $item['title']],
                [
                    ...$item,
                    'published' => true,
                    'published_at' => now()->subDays($index + 1),
                ]
            );
        }
    }
}
