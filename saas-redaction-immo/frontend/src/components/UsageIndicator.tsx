'use client';

import { useEffect, useState } from 'react';
import { Crown, AlertCircle } from 'lucide-react';
import Link from 'next/link';
import { getUsage } from '@/lib/api';
import type { UsageResponse } from '@/types';

export default function UsageIndicator() {
  const [usage, setUsage] = useState<UsageResponse | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchUsage = async () => {
      try {
        const data = await getUsage();
        setUsage(data);
      } catch (err) {
        console.error('Erreur lors de la récupération de l\'utilisation');
      } finally {
        setLoading(false);
      }
    };
    fetchUsage();
  }, []);

  if (loading) {
    return (
      <div className="bg-gray-100 rounded-lg p-4 animate-pulse">
        <div className="h-4 bg-gray-200 rounded w-1/2 mb-2" />
        <div className="h-2 bg-gray-200 rounded w-full" />
      </div>
    );
  }

  if (!usage) return null;

  const isPro = usage.plan === 'pro';
  const percentage = isPro ? 100 : (usage.used / usage.limit) * 100;
  const isLimitReached = !isPro && usage.remaining === 0;

  return (
    <div className={`rounded-lg p-4 ${isPro ? 'bg-yellow-50 border border-yellow-200' : isLimitReached ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200'}`}>
      <div className="flex items-center justify-between mb-2">
        <div className="flex items-center space-x-2">
          {isPro ? (
            <>
              <Crown className="w-5 h-5 text-yellow-600" />
              <span className="font-medium text-yellow-800">Plan Pro</span>
            </>
          ) : isLimitReached ? (
            <>
              <AlertCircle className="w-5 h-5 text-red-600" />
              <span className="font-medium text-red-800">Limite atteinte</span>
            </>
          ) : (
            <span className="font-medium text-blue-800">Plan Gratuit</span>
          )}
        </div>
        {!isPro && (
          <span className="text-sm text-gray-600">
            {usage.used}/{usage.limit} ce mois
          </span>
        )}
      </div>

      {!isPro && (
        <>
          <div className="w-full bg-gray-200 rounded-full h-2 mb-3">
            <div
              className={`h-2 rounded-full transition-all ${isLimitReached ? 'bg-red-500' : 'bg-blue-500'}`}
              style={{ width: `${Math.min(percentage, 100)}%` }}
            />
          </div>

          {isLimitReached ? (
            <p className="text-sm text-red-700 mb-3">
              Vous avez atteint votre limite mensuelle de {usage.limit} annonces.
            </p>
          ) : (
            <p className="text-sm text-gray-600 mb-3">
              Il vous reste {usage.remaining} génération{usage.remaining > 1 ? 's' : ''} ce mois-ci.
            </p>
          )}

          <Link
            href="/app/pricing"
            className="inline-flex items-center space-x-1 text-sm font-medium text-primary-600 hover:text-primary-700"
          >
            <Crown className="w-4 h-4" />
            <span>Passer au Pro pour illimité</span>
          </Link>
        </>
      )}

      {isPro && (
        <p className="text-sm text-yellow-700">
          Générations illimitées incluses dans votre abonnement.
        </p>
      )}
    </div>
  );
}
