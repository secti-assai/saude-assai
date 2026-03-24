import { usePage } from '@inertiajs/react';
import React from 'react';
import AuthLayout from '@/Layouts/AuthLayout';
import AdminDashboard from './Partials/AdminDashboard';
import PharmacyDashboard from './Partials/PharmacyDashboard';
import DoctorDashboard from './Partials/DoctorDashboard';
import { PageProps } from '@/types';

export default function Dashboard() {
  const { auth } = usePage<PageProps>().props;
  const user = auth.user;

  if (!user) return null;

  const renderDashboardByProfile = () => {
    switch (user.profile) {
      case 'admin':
        return <AdminDashboard />;
      case 'pharmacist':
        return <PharmacyDashboard />;
      case 'doctor':
      case 'nurse':
        return <DoctorDashboard />; // Enfermeiros partilham visão inicial hospitalar neste escopo
      default:
        return (
          <div className="bg-white p-6 rounded shadow text-center">
            Módulo específico do seu perfil ({user.profile}) em construção ou não atribuído.
          </div>
        );
    }
  };

  return (
    <AuthLayout header={`Painel Controle - ${user.profile.toUpperCase()}`}>
      {renderDashboardByProfile()}
    </AuthLayout>
  );
}
