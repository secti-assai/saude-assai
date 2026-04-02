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
        Schema::create('womens_clinic_appointments', function (Blueprint $table) {
            // Regra Ouro (Skill): UUID
            $table->uuid('id')->primary();
            
            // Aba 1: Identificação do Cidadão (Mapeamento Thrift)
            $table->string('cns', 15)->nullable()->index();
            $table->string('cpf', 11)->nullable()->index();
            $table->date('data_nascimento');
            $table->tinyInteger('sexo')->comment('0 = Masculino, 1 = Feminino');
            $table->string('nome_completo', 255);
            $table->string('telefone_celular', 15)->nullable();

            // Aba 2: Motivo (FichaAtendimentoIndividualChildThrift)
            $table->tinyInteger('turno');
            $table->tinyInteger('tipo_atendimento');
            $table->string('ciap_principal', 8)->nullable();
            $table->string('cid_principal', 8)->nullable();
            
            // Aba 3: Clínica da Mulher / Antropometria
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->date('dum')->nullable(); // DUM
            $table->tinyInteger('idade_gestacional')->nullable();
            $table->boolean('st_gravidez_planejada')->nullable();
            $table->tinyInteger('nu_gestas_previas')->nullable();
            $table->tinyInteger('nu_partos')->nullable();

            // Rastreabilidade
            $table->uuid('uuid_transporte')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Regra Ouro (Skill): LGPD
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('womens_clinic_appointments');
    }
};
