import { Link, usePage } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { PageProps } from '@/types';

interface AuthLayoutProps {
  children: ReactNode;
  header?: string | ReactNode;
}

export default function AuthLayout({ children, header }: AuthLayoutProps) {
  const { auth } = usePage<PageProps>().props;
  const user = auth.user;

  // Fallback se nÃ£o logado (caso ocorra um bypass acidental)
  if (!user) {
    return <div>Carregando ou NÃ£o Autorizado...</div>;
  }

  const profile = user.profile;

  // Controle de Abas (RBAC)
  const menuItems = [
    { name: 'Dashboard Geral', href: '/dashboard', roles: ['admin'] },
    { name: 'Fila de Dispensação', href: '/farmacia', roles: ['pharmacist', 'admin'] },
    { name: 'Prontuário Hospitalar', href: '/hospital', roles: ['doctor', 'nurse', 'admin'] },
    { name: 'Triagem UBS', href: '/triagem', roles: ['nurse', 'admin'] },
    { name: 'Recepção', href: '/recepcao', roles: ['receptionist', 'admin'] },
    { name: 'Auditoria e Usuários', href: '/admin/usuarios', roles: ['admin'] },
  ];

  return (
    <div className="min-h-screen bg-assai-surface flex">
      {/* Sidebar Responsiva */}
      <aside className="w-64 bg-assai-primary text-white hidden md:flex flex-col shadow-lg">
        <div className="p-4 border-b border-white/10 flex flex-col gap-1">
          <span className="text-2xl font-bold tracking-tight">SaÃºde AssaÃ­</span>
          <span className="text-xs text-white/80">GestÃ£o Municipal</span>
        </div>

        <nav className="flex-1 py-4 overflow-y-auto">
          <ul className="space-y-1">
            {menuItems
              .filter(item => item.roles.includes(profile))
              .map(item => (
                <li key={item.name}>
                  <Link 
                    href={item.href}
                    className="block px-6 py-3 hover:bg-assai-secondary transition-colors focus:outline-none focus:bg-assai-secondary focus:ring-2 focus:ring-inset focus:ring-white"
                    aria-label={`Acessar ${item.name}`}
                  >
                    {item.name}
                  </Link>
                </li>
            ))}
          </ul>
        </nav>

        {/* IdentificaÃ§Ã£o de Operador (LGPD/Rastreabilidade) */}
        <div className="p-4 border-t border-white/10 text-sm bg-black/10">
          <p className="font-semibold text-white truncate" title={user.name}>{user.name}</p>
          <p className="text-white/70 capitalize">{profile}</p>
          <Link 
            href="/logout" 
            method="post" 
            as="button" 
            className="mt-2 text-xs text-red-200 hover:text-red-100 hover:underline"
          >
            Sair do Sistema
          </Link>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col overflow-hidden w-full">
        {/* Topbar para Mobile (Hamburguer) e TÃ­tulo da PÃ¡gina */}
        <header className="bg-white shadow z-10">
          <div className="flex justify-between items-center px-6 py-4">
            <h1 className="text-xl font-semibold text-gray-800">{header}</h1>
            <button 
              className="md:hidden p-2 rounded-md hover:bg-gray-100 text-assai-primary focus:outline-none"
              aria-label="Abrir menu"
            >
              {/* Ãcone Menu Hamburguer - Lucide React ou SVG direto */}
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            </button>
          </div>
        </header>

        {/* Content Area */}
        <div className="flex-1 overflow-auto bg-assai-surface">
          <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
             {children}
          </div>
        </div>
      </main>
    </div>
  );
}
