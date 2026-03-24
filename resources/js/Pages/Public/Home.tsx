import { Link } from '@inertiajs/react';
import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Home() {
  return (
    <PublicLayout>
      {/* Banner Principal ACESSÃVEL */}
      <section className="bg-white rounded-2xl shadow-sm overflow-hidden mb-12 flex flex-col md:flex-row items-center border border-gray-100">
        <div className="p-8 md:p-12 md:w-1/2 flex flex-col gap-6">
          <h1 className="text-4xl md:text-5xl font-black text-gray-900 leading-tight">
            Sua Saúde, <br/><span className="text-assai-primary text-transparent bg-clip-text bg-gradient-to-r from-assai-primary to-assai-secondary">Nossa Prioridade.</span>
          </h1>
          <p className="text-lg text-gray-600 font-medium">
            Agende consultas, solicite remédios em casa e acompanhe o histórico médico de sua famÃ­lia. Tudo de forma totalmente digital e integrada.
          </p>
          <div className="flex flex-wrap gap-4 mt-4">
             <a href="#servicos" className="px-6 py-3 bg-assai-primary text-white font-bold rounded-md hover:bg-assai-secondary focus:ring-4 focus:ring-assai-primary/30 transition-all text-center flex-1 sm:flex-none">
                Acessar Serviços
             </a>
             <Link href="/emergencia" className="px-6 py-3 bg-manchester-red text-white font-bold rounded-md hover:bg-red-700 focus:ring-4 focus:ring-red-500/30 transition-all text-center flex-1 sm:flex-none flex justify-center items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Onde ir na Urgência?
             </Link>
          </div>
        </div>
        <div className="md:w-1/2 bg-gray-50 h-full p-8 flex items-center justify-center">
            {/* Imagem Placeholder representativa - ilustraÃ§Ã£o de Saúde */}
            <div className="w-full aspect-video bg-assai-secondary/10 rounded-xl border-2 border-dashed border-assai-primary/20 flex items-center justify-center text-assai-primary/50 font-bold">
               [ Espaço para Banner Institucional / Foto da Fachada UBS ]
            </div>
        </div>
      </section>

      {/* Grid de Serviços Rápidos (Alta legibilidade e Cores consistentes) */}
      <section id="servicos" className="mb-12">
        <h2 className="text-3xl font-black text-gray-800 mb-8 border-l-8 border-assai-primary pl-4">Acesso Rápido</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          
          {/* Card: Remédio em Casa */}
          <Link href="/remedio-em-casa" className="group block h-full">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full transition-all group-hover:shadow-md group-hover:border-assai-primary group-focus:ring-2 group-focus:ring-assai-primary outline-none">
              <div className="w-12 h-12 bg-assai-primary/10 rounded-lg flex items-center justify-center mb-4 text-assai-primary">
                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-assai-primary">Remédio em Casa</h3>
              <p className="text-gray-600">Acompanhe as entregas das suas prescriÃ§Ãµes contÃ­nuas diretamente no seu endereÃ§o.</p>
            </div>
          </Link>

          {/* Card: ProntuÃ¡rio cidadão */}
          <Link href="/meu-prontuario" className="group block h-full">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full transition-all group-hover:shadow-md group-hover:border-assai-primary group-focus:ring-2 group-focus:ring-assai-primary outline-none">
              <div className="w-12 h-12 bg-assai-accent/10 rounded-lg flex items-center justify-center mb-4 text-assai-accent">
                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-assai-primary">Meu histórico (Gov.BR)</h3>
              <p className="text-gray-600">Visualize vacinas, exames, histórico clÃ­nico e resumos de alta utilizando seu login Ãºnico.</p>
            </div>
          </Link>

           {/* Card: Onde ser atendido? */}
           <Link href="/unidades" className="group block h-full">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full transition-all group-hover:shadow-md group-hover:border-assai-primary group-focus:ring-2 group-focus:ring-assai-primary outline-none">
              <div className="w-12 h-12 bg-assai-secondary/10 rounded-lg flex items-center justify-center mb-4 text-assai-secondary">
                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-assai-primary">Farmácias e UBSs</h3>
              <p className="text-gray-600">Encontre a Unidade BÃ¡sica de Saúde (posto) mais prÃ³xima da sua residÃªncia.</p>
            </div>
          </Link>

        </div>
      </section>

      {/* Informativo de TransparÃªncia */}
      <section className="bg-gradient-to-br from-assai-primary to-assai-secondary rounded-2xl p-8 md:p-12 text-white shadow-lg text-center md:text-left flex flex-col md:flex-row items-center justify-between gap-8 gap-y-12">
         <div className="md:w-1/2">
            <h2 className="text-3xl font-black mb-4">Saúde em Números</h2>
            <p className="text-white/80 font-medium">
               Acompanhe a transparÃªncia dos atendimentos no municÃ­pio de Assaí­, em conformidade com o Gover.Assaí­.
            </p>
         </div>
         <div className="grid grid-cols-2 gap-8 w-full md:w-1/2">
            <div className="text-center">
               <span className="block text-4xl sm:text-5xl font-black mb-2">12k+</span>
               <span className="text-sm text-white/80 uppercase tracking-widest font-bold">Atendimentos no MÃªs</span>
            </div>
            <div className="text-center">
               <span className="block text-4xl sm:text-5xl font-black mb-2 flex items-center justify-center"><svg className="h-10 w-10 text-assai-accent mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>98%</span>
               <span className="text-sm text-white/80 uppercase tracking-widest font-bold">SatisfaÃ§Ã£o cidadão</span>
            </div>
         </div>
      </section>
    </PublicLayout>
  );
}
