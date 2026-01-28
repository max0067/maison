'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { User, Crown, CreditCard, Settings, Loader2 } from 'lucide-react';
import Header from '@/components/Header';
import UsageIndicator from '@/components/UsageIndicator';
import { useAuth } from '@/hooks/useAuth';
import { createCheckout, createPortalSession, getSubscriptionStatus } from '@/lib/api';

export default function AccountPage() {
  const router = useRouter();
  const { user, loading: authLoading, isAuthenticated } = useAuth();
  const [loading, setLoading] = useState(false);
  const [subscription, setSubscription] = useState<any>(null);

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/login');
    }
  }, [authLoading, isAuthenticated, router]);

  useEffect(() => {
    const fetchSubscription = async () => {
      try {
        const data = await getSubscriptionStatus();
        setSubscription(data.subscription);
      } catch (err) {
        console.error('Erreur lors de la récupération de l\'abonnement');
      }
    };

    if (isAuthenticated) {
      fetchSubscription();
    }
  }, [isAuthenticated]);

  const handleUpgrade = async () => {
    setLoading(true);
    try {
      const { checkout_url } = await createCheckout();
      window.location.href = checkout_url;
    } catch (err: any) {
      alert(err.message || 'Erreur lors de la création de la session de paiement');
    } finally {
      setLoading(false);
    }
  };

  const handleManageSubscription = async () => {
    setLoading(true);
    try {
      const { portal_url } = await createPortalSession();
      window.location.href = portal_url;
    } catch (err: any) {
      alert(err.message || 'Erreur lors de l\'accès au portail');
    } finally {
      setLoading(false);
    }
  };

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!isAuthenticated || !user) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Mon compte</h1>
          <p className="text-gray-600">
            Gérez votre profil et votre abonnement.
          </p>
        </div>

        <div className="grid gap-6">
          {/* Profil */}
          <div className="card">
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <User className="w-5 h-5 text-primary-600" />
              </div>
              <h2 className="text-xl font-semibold text-gray-900">Profil</h2>
            </div>
            <div className="space-y-4">
              <div>
                <label className="text-sm text-gray-500">Email</label>
                <p className="text-gray-900">{user.email}</p>
              </div>
              <div>
                <label className="text-sm text-gray-500">Membre depuis</label>
                <p className="text-gray-900">
                  {new Date(user.created_at).toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                  })}
                </p>
              </div>
            </div>
          </div>

          {/* Utilisation */}
          <div className="card">
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <Settings className="w-5 h-5 text-primary-600" />
              </div>
              <h2 className="text-xl font-semibold text-gray-900">Utilisation</h2>
            </div>
            <UsageIndicator />
          </div>

          {/* Abonnement */}
          <div className="card">
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <Crown className="w-5 h-5 text-yellow-600" />
              </div>
              <h2 className="text-xl font-semibold text-gray-900">Abonnement</h2>
            </div>

            {user.plan === 'pro' ? (
              <div className="space-y-4">
                <div className="flex items-center space-x-2">
                  <span className="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                    Plan Pro
                  </span>
                  <span className="text-gray-500">- Actif</span>
                </div>
                {subscription && (
                  <p className="text-sm text-gray-600">
                    Prochain renouvellement :{' '}
                    {new Date(subscription.current_period_end * 1000).toLocaleDateString('fr-FR')}
                  </p>
                )}
                <button
                  onClick={handleManageSubscription}
                  disabled={loading}
                  className="btn btn-secondary flex items-center space-x-2"
                >
                  {loading ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <CreditCard className="w-4 h-4" />
                  )}
                  <span>Gérer mon abonnement</span>
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                <div className="flex items-center space-x-2">
                  <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
                    Plan Gratuit
                  </span>
                </div>
                <p className="text-gray-600">
                  Passez au plan Pro pour des générations illimitées et un historique permanent.
                </p>
                <div className="bg-primary-50 border border-primary-100 rounded-lg p-4">
                  <div className="flex justify-between items-center mb-3">
                    <span className="font-medium text-gray-900">Plan Pro</span>
                    <span className="text-2xl font-bold text-primary-600">19€/mois</span>
                  </div>
                  <ul className="text-sm text-gray-600 space-y-1 mb-4">
                    <li>- Annonces illimitées</li>
                    <li>- Historique permanent</li>
                    <li>- Support prioritaire</li>
                  </ul>
                  <button
                    onClick={handleUpgrade}
                    disabled={loading}
                    className="w-full btn btn-primary flex items-center justify-center space-x-2"
                  >
                    {loading ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <>
                        <Crown className="w-4 h-4" />
                        <span>Passer au Pro</span>
                      </>
                    )}
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
