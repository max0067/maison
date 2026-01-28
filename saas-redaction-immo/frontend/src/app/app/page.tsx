'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { AlertCircle } from 'lucide-react';
import Header from '@/components/Header';
import RewriteForm from '@/components/RewriteForm';
import ResultDisplay from '@/components/ResultDisplay';
import UsageIndicator from '@/components/UsageIndicator';
import { useAuth } from '@/hooks/useAuth';
import type { RewriteResponse } from '@/types';

export default function AppPage() {
  const router = useRouter();
  const { user, loading, isAuthenticated } = useAuth();
  const [result, setResult] = useState<RewriteResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      router.push('/login');
    }
  }, [loading, isAuthenticated, router]);

  const handleResult = (data: RewriteResponse) => {
    setResult(data);
    setError(null);
    // Scroll to results
    window.scrollTo({ top: 400, behavior: 'smooth' });
  };

  const handleError = (message: string) => {
    setError(message);
    setResult(null);
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Réécrire une annonce
          </h1>
          <p className="text-gray-600">
            Transformez votre annonce brute en texte professionnel qui donne envie de visiter.
          </p>
        </div>

        {/* Usage Indicator */}
        <div className="mb-6">
          <UsageIndicator />
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-6 flex items-center space-x-2 p-4 bg-red-50 border border-red-200 rounded-lg">
            <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
            <span className="text-red-700">{error}</span>
          </div>
        )}

        {/* Rewrite Form */}
        <div className="mb-8">
          <RewriteForm onResult={handleResult} onError={handleError} />
        </div>

        {/* Results */}
        {result && <ResultDisplay result={result} />}
      </main>
    </div>
  );
}
