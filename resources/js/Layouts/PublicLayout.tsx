import { Link } from '@inertiajs/react';
import React, { ReactNode } from 'react';

interface PublicLayoutProps {
  children: ReactNode;
}

export default function PublicLayout({ children }: PublicLayoutProps) {
  return (
    <div className="min-h-screen bg-assai-surface flex flex-col font-sans">
      {/* Header AcessÃ­vel Público */}
      <header className="bg-white text-assai-primary shadow-sm border-b-2 border-assai-secondary sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
          
          <div className="flex items-center gap-2">
            <span className="text-2xl font-black tracking-tighter">Saúde Assaí­</span>
          </div>

          {/* Navegação Principal (Limpa e Direta) */}
          <nav aria-label="Navegação Principal">
            <ul className="flex items-center gap-6">
              <li>
                <Link href="/" className="font-semibold text-gray-700 hover:text-assai-primary transition-colors focus:outline-none focus:underline underline-offset-4">
                  InÃ­cio
                </Link>
              </li>
              <li>
                <Link href="/unidades" className="font-semibold text-gray-700 hover:text-assai-primary transition-colors focus:outline-none focus:underline underline-offset-4">
                  Unidades (UBS)
                </Link>
              </li>
              <li>
                <Link href="/remedio-em-casa" className="font-semibold text-gray-700 hover:text-assai-primary transition-colors focus:outline-none focus:underline underline-offset-4">
                  RemÃ©dio em Casa
                </Link>
              </li>
              <li>
                <Link 
                  href="/login" 
                  className="px-4 py-2 bg-assai-primary text-white font-bold rounded-md hover:bg-assai-secondary focus:ring-2 focus:ring-offset-2 focus:ring-assai-primary transition-all"
                  aria-label="Entrar como Servidor"
                >
                  Acesso Servidor
                </Link>
              </li>
            </ul>
          </nav>
        </div>
      </header>

      {/* Main Content (PWA Ready) */}
      <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="main-content" tabIndex={-1}>
        {children}
      </main>

      {/* Footer Público */}
      <footer className="bg-assai-primary text-white py-8 mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-left flex flex-col sm:flex-row justify-between items-center gap-4">
          <div>
            <p className="font-bold text-lg">Prefeitura Municipal de Assaí­ - PR</p>
            <p className="text-sm text-white/80">DivisÃ£o de CiÃªncia, Tecnologia e InovaÃ§Ã£o</p>
          </div>
          <div className="text-sm text-white/60">
            &copy; {new Date().getFullYear()} - Sistema Integrado de Saúde.
          </div>
        </div>
      </footer>
    </div>
  );
}
