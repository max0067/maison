'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { FileText, Calendar, Trash2, ChevronLeft, ChevronRight, Eye } from 'lucide-react';
import Header from '@/components/Header';
import { useAuth } from '@/hooks/useAuth';
import { getHistory, deleteAnnonce } from '@/lib/api';
import type { AnnonceList, AnnonceListItem } from '@/types';

function ScoreBadge({ score }: { score: number | null }) {
  if (score === null) return <span className="text-gray-400">-</span>;

  const getColorClass = (score: number) => {
    if (score >= 75) return 'score-green';
    if (score >= 50) return 'score-orange';
    return 'score-red';
  };

  return (
    <span className={`score-badge ${getColorClass(score)}`}>
      {Math.round(score)}/100
    </span>
  );
}

function formatDate(dateString: string) {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getTypeBienLabel(type: string) {
  const labels: Record<string, string> = {
    appartement: 'Appartement',
    maison: 'Maison',
    terrain: 'Terrain',
  };
  return labels[type] || type;
}

function getTransactionLabel(type: string) {
  const labels: Record<string, string> = {
    vente: 'Vente',
    location: 'Location',
  };
  return labels[type] || type;
}

export default function HistoryPage() {
  const router = useRouter();
  const { loading: authLoading, isAuthenticated } = useAuth();
  const [history, setHistory] = useState<AnnonceList | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [deleting, setDeleting] = useState<number | null>(null);

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/login');
    }
  }, [authLoading, isAuthenticated, router]);

  useEffect(() => {
    const fetchHistory = async () => {
      try {
        const data = await getHistory(page, 10);
        setHistory(data);
      } catch (err) {
        console.error('Erreur lors de la récupération de l\'historique');
      } finally {
        setLoading(false);
      }
    };

    if (isAuthenticated) {
      fetchHistory();
    }
  }, [page, isAuthenticated]);

  const handleDelete = async (id: number) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')) return;

    setDeleting(id);
    try {
      await deleteAnnonce(id);
      setHistory((prev) => {
        if (!prev) return prev;
        return {
          ...prev,
          items: prev.items.filter((item) => item.id !== id),
          total: prev.total - 1,
        };
      });
    } catch (err) {
      console.error('Erreur lors de la suppression');
    } finally {
      setDeleting(null);
    }
  };

  if (authLoading || loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  const totalPages = history ? Math.ceil(history.total / history.per_page) : 0;

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <main className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8 flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Historique</h1>
            <p className="text-gray-600">
              Retrouvez toutes vos annonces générées.
            </p>
          </div>
          <Link href="/app" className="btn btn-primary">
            Nouvelle annonce
          </Link>
        </div>

        {!history || history.items.length === 0 ? (
          <div className="card text-center py-12">
            <FileText className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              Aucune annonce
            </h3>
            <p className="text-gray-500 mb-4">
              Vous n'avez pas encore généré d'annonces.
            </p>
            <Link href="/app" className="btn btn-primary">
              Créer ma première annonce
            </Link>
          </div>
        ) : (
          <>
            <div className="card overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-50 border-b border-gray-200">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Annonce
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Score
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {history.items.map((item) => (
                    <tr key={item.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4">
                        <p className="text-sm text-gray-900 line-clamp-2">
                          {item.texte_original}
                        </p>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className="text-sm text-gray-600">
                          {getTypeBienLabel(item.type_bien)}
                        </span>
                        <br />
                        <span className="text-xs text-gray-400">
                          {getTransactionLabel(item.type_transaction)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <ScoreBadge score={item.score_global} />
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center text-sm text-gray-500">
                          <Calendar className="w-4 h-4 mr-1" />
                          {formatDate(item.created_at)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right">
                        <div className="flex items-center justify-end space-x-2">
                          <Link
                            href={`/app/annonce/${item.id}`}
                            className="p-2 text-gray-500 hover:text-primary-600 transition-colors"
                            title="Voir"
                          >
                            <Eye className="w-4 h-4" />
                          </Link>
                          <button
                            onClick={() => handleDelete(item.id)}
                            disabled={deleting === item.id}
                            className="p-2 text-gray-500 hover:text-red-600 transition-colors"
                            title="Supprimer"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="mt-6 flex items-center justify-between">
                <p className="text-sm text-gray-500">
                  Page {page} sur {totalPages} ({history.total} annonces)
                </p>
                <div className="flex space-x-2">
                  <button
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                    disabled={page === 1}
                    className="btn btn-secondary disabled:opacity-50"
                  >
                    <ChevronLeft className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                    disabled={page === totalPages}
                    className="btn btn-secondary disabled:opacity-50"
                  >
                    <ChevronRight className="w-4 h-4" />
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </main>
    </div>
  );
}
