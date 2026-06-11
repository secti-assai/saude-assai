<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecoverDeletedUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recover:user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera um usuário deletado e seus registros apagados por cascata a partir do banco de backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Buscando usuário com email {$email} no banco de backup...");

        try {
            $backupDb = DB::connection('backup');
            $prodDb = DB::connection('pgsql'); // Conexão principal de produção
        } catch (\Exception $e) {
            $this->error("Erro ao conectar nos bancos: " . $e->getMessage());
            $this->info("Certifique-se de ter configurado as variáveis DB_BACKUP_* no seu arquivo .env");
            return;
        }

        $user = $backupDb->table('users')->where('email', $email)->first();

        if (!$user) {
            $this->error("Usuário não encontrado no banco de backup.");
            return;
        }

        $this->info("Usuário antigo encontrado no backup: {$user->name} (ID: {$user->id})");

        // 1. Recuperar Usuário
        $prodUser = $prodDb->table('users')->where('email', $email)->first();
        
        $targetUserId = $user->id;

        if ($prodUser) {
            if ($prodUser->id !== $user->id) {
                $this->warn("⚠️ Foi encontrado um usuário recém-criado em produção com o mesmo e-mail (ID novo: {$prodUser->id}).");
                $this->info("🔄 Os agendamentos antigos serão associados a esta nova conta para evitar erro de duplicação.");
                $targetUserId = $prodUser->id;
            } else {
                // Restore deleted_at to null in case it was soft deleted
                $prodDb->table('users')->where('id', $user->id)->update(['deleted_at' => null]);
                $this->info("⚠️ O usuário já existe no banco de produção com o mesmo ID original.");
            }
        } else {
            $prodDb->table('users')->insert((array) $user);
            $this->info("✅ Usuário original reinserido com sucesso.");
        }

        // 2. Recuperar Agendamentos (Clinica da Mulher / Policlinica)
        $appointments = $backupDb->table('women_clinic_appointments')
            ->where('scheduler_user_id', $user->id)
            ->orWhere('reception_user_id', $user->id)
            ->orWhere('doctor_user_id', $user->id)
            ->get();

        $countAppt = 0;
        foreach ($appointments as $appt) {
            $exists = $prodDb->table('women_clinic_appointments')->where('id', $appt->id)->exists();
            if (!$exists) {
                $apptArray = (array) $appt;
                // Redireciona para o ID do usuário novo (se aplicável)
                if ($apptArray['scheduler_user_id'] == $user->id) $apptArray['scheduler_user_id'] = $targetUserId;
                if ($apptArray['reception_user_id'] == $user->id) $apptArray['reception_user_id'] = $targetUserId;
                if ($apptArray['doctor_user_id'] == $user->id) $apptArray['doctor_user_id'] = $targetUserId;
                
                $prodDb->table('women_clinic_appointments')->insert($apptArray);
                $countAppt++;
            }
        }
        $this->info("✅ Foram recuperados {$countAppt} agendamentos da Clínica da Mulher / Policlínica.");

        // 3. Recuperar Solicitações da Farmácia Central
        $pharmacyReqs = $backupDb->table('central_pharmacy_requests')
            ->where('reception_user_id', $user->id)
            ->get();
            
        $countPharm = 0;
        foreach ($pharmacyReqs as $req) {
            $exists = $prodDb->table('central_pharmacy_requests')->where('id', $req->id)->exists();
            if (!$exists) {
                $reqArray = (array) $req;
                if ($reqArray['reception_user_id'] == $user->id) $reqArray['reception_user_id'] = $targetUserId;

                $prodDb->table('central_pharmacy_requests')->insert($reqArray);
                $countPharm++;
            }
        }
        $this->info("✅ Foram recuperados {$countPharm} solicitações da Farmácia Central.");

        // 4. Recuperar Importações Externas da Farmácia
        if (Schema::connection('backup')->hasTable('pharmacy_external_imports')) {
            $imports = $backupDb->table('pharmacy_external_imports')
                ->where('uploaded_by_user_id', $user->id)
                ->get();
                
            $countImports = 0;
            foreach ($imports as $imp) {
                $exists = $prodDb->table('pharmacy_external_imports')->where('id', $imp->id)->exists();
                if (!$exists) {
                    $impArray = (array) $imp;
                    if ($impArray['uploaded_by_user_id'] == $user->id) $impArray['uploaded_by_user_id'] = $targetUserId;

                    $prodDb->table('pharmacy_external_imports')->insert($impArray);
                    $countImports++;
                }
            }
            $this->info("✅ Foram recuperados {$countImports} importações externas.");
        }

        $this->info("🎉 Recuperação finalizada com sucesso!");
    }
}
