'use client';

import Link from 'next/link';
import { useAuth } from '@/hooks/useAuth';
import { Home, History, User, LogOut, Crown } from 'lucide-react';

export default function Header() {
  const { user, loading, logout, isAuthenticated } = useAuth();

  return (
    <header className="bg-white shadow-sm border-b border-gray-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link href="/" className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
              <Home className="w-5 h-5 text-white" />
            </div>
            <span className="text-xl font-bold text-gray-900">RedacImmo</span>
          </Link>

          {/* Navigation */}
          <nav className="flex items-center space-x-4">
            {loading ? (
              <div className="w-24 h-8 bg-gray-200 rounded animate-pulse" />
            ) : isAuthenticated ? (
              <>
                <Link
                  href="/app"
                  className="flex items-center space-x-1 text-gray-600 hover:text-primary-600 transition-colors"
                >
                  <Home className="w-4 h-4" />
                  <span>Rédiger</span>
                </Link>
                <Link
                  href="/app/history"
                  className="flex items-center space-x-1 text-gray-600 hover:text-primary-600 transition-colors"
                >
                  <History className="w-4 h-4" />
                  <span>Historique</span>
                </Link>
                <Link
                  href="/app/account"
                  className="flex items-center space-x-1 text-gray-600 hover:text-primary-600 transition-colors"
                >
                  <User className="w-4 h-4" />
                  <span>Compte</span>
                </Link>
                {user?.plan === 'pro' && (
                  <span className="flex items-center space-x-1 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                    <Crown className="w-3 h-3" />
                    <span>Pro</span>
                  </span>
                )}
                <button
                  onClick={logout}
                  className="flex items-center space-x-1 text-gray-500 hover:text-red-600 transition-colors"
                >
                  <LogOut className="w-4 h-4" />
                  <span>Déconnexion</span>
                </button>
              </>
            ) : (
              <>
                <Link
                  href="/login"
                  className="text-gray-600 hover:text-primary-600 transition-colors"
                >
                  Connexion
                </Link>
                <Link href="/register" className="btn btn-primary">
                  Commencer gratuitement
                </Link>
              </>
            )}
          </nav>
        </div>
      </div>
    </header>
  );
}
