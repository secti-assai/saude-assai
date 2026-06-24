<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triagem_pacientes', function (Blueprint $table) {
            $table->id();

            // Dados pessoais obrigatórios
            $table->string('cpf', 11)->nullable()->index();
            $table->string('cns', 15)->nullable();
            $table->string('full_name');
            $table->string('social_name')->nullable();
            $table->date('birth_date');
            $table->string('sexo', 1)->nullable();          // M / F / I
            $table->string('raca_cor')->nullable();
            $table->string('etnia')->nullable();
            $table->string('nome_mae')->nullable();
            $table->string('nome_pai')->nullable();
            $table->string('municipio_nascimento')->nullable();
            $table->string('phone')->nullable();

            // Controle de atendimento
            $table->string('status')->default('AGUARDANDO');  // AGUARDANDO | EM_TRIAGEM | FINALIZADO
            $table->string('priority_color')->default('AZUL');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('triagem_started_at')->nullable();
            $table->timestamp('triagem_finished_at')->nullable();

            // Responsável pelo cadastro e atendimento
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nurse_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('health_unit_id')->nullable()->constrained('health_units')->nullOnDelete();

            // Link para o cidadão da base principal (preenchido se o cidadão já existir ou for vinculado)
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triagem_pacientes');
    }
};
