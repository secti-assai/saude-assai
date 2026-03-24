import { useForm, Head } from '@inertiajs/react';
import React, { FormEventHandler } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Login() {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    post('/login');
  };

  return (
    <PublicLayout>
      <Head title="Acesso do Servidor" />
      <div className="flex flex-col items-center min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8">
        <div className="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
          <h2 className="text-2xl font-black text-gray-900 mb-6 text-center">
            Acesso ao Sistema
          </h2>

          <form onSubmit={submit} className="flex flex-col gap-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                E-mail ou CPF
              </label>
              <input
                id="email"
                type="email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-assai-primary focus:ring-assai-primary sm:text-sm p-3 border"
              />
              {errors.email && <p className="text-red-600 text-sm mt-1">{errors.email}</p>}
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                Senha
              </label>
              <input
                id="password"
                type="password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-assai-primary focus:ring-assai-primary sm:text-sm p-3 border"
              />
              {errors.password && <p className="text-red-600 text-sm mt-1">{errors.password}</p>}
            </div>

            <div className="flex items-center justify-between mt-2">
              <div className="flex items-center">
                <input
                  id="remember"
                  type="checkbox"
                  checked={data.remember}
                  onChange={(e) => setData('remember', e.target.checked)}
                  className="rounded border-gray-300 text-assai-primary focus:ring-assai-primary"
                />
                <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                  Lembrar de mim
                </label>
              </div>
            </div>

            <button
              type="submit"
              disabled={processing}
              className="mt-4 w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-assai-primary hover:bg-assai-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-assai-primary disabled:opacity-50 transition-colors"
            >
              {processing ? 'Entrando...' : 'Entrar'}
            </button>
          </form>
        </div>
      </div>
    </PublicLayout>
  );
}