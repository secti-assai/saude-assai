<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gov_assai_eventos_queue', function (Blueprint $table) {
            $table->id();
            $table->string('origem_evento_id', 120)->unique();
            $table->string('cpf', 11);
            $table->string('tipo_evento', 60);
            $table->string('status_evento', 40);
            $table->string('servico_utilizado', 200);
            $table->string('estabelecimento', 200)->nullable();
            $table->timestamp('data_hora');
            $table->text('descricao')->nullable();
            $table->jsonb('dados_adicionais')->nullable();
            $table->jsonb('payload_json');
            $table->string('status_envio', 20)->default('pendente');
            $table->integer('tentativas')->default(0);
            $table->timestamp('ultima_tentativa_em')->nullable();
            $table->integer('ultima_resposta_http')->nullable();
            $table->text('ultima_resposta_body')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();

            $table->index('status_envio');
            $table->index('cpf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gov_assai_eventos_queue');
    }
};
