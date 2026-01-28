'use client';

import { useState } from 'react';
import { Wand2, FileText, Zap, Heart, Loader2 } from 'lucide-react';
import type { TypeBien, TypeTransaction, Gamme, TonRedaction, RewriteResponse } from '@/types';
import { rewriteAnnonce, rewriteSingleVersion } from '@/lib/api';

interface RewriteFormProps {
  onResult: (result: RewriteResponse) => void;
  onError: (error: string) => void;
}

export default function RewriteForm({ onResult, onError }: RewriteFormProps) {
  const [texte, setTexte] = useState('');
  const [typeBien, setTypeBien] = useState<TypeBien>('appartement');
  const [typeTransaction, setTypeTransaction] = useState<TypeTransaction>('vente');
  const [gamme, setGamme] = useState<Gamme>('standard');
  const [loading, setLoading] = useState(false);
  const [loadingType, setLoadingType] = useState<string | null>(null);

  const handleSubmitAll = async () => {
    if (texte.length < 50) {
      onError('Veuillez entrer une annonce d\'au moins 50 caractères.');
      return;
    }

    setLoading(true);
    setLoadingType('all');
    try {
      const result = await rewriteAnnonce({
        texte_original: texte,
        type_bien: typeBien,
        type_transaction: typeTransaction,
        gamme: gamme,
        ton: 'professionnel',
      });
      onResult(result);
    } catch (err: any) {
      onError(err.message || 'Erreur lors de la réécriture');
    } finally {
      setLoading(false);
      setLoadingType(null);
    }
  };

  const handleSubmitSingle = async (ton: TonRedaction) => {
    if (texte.length < 50) {
      onError('Veuillez entrer une annonce d\'au moins 50 caractères.');
      return;
    }

    setLoading(true);
    setLoadingType(ton);
    try {
      const result = await rewriteSingleVersion({
        texte_original: texte,
        type_bien: typeBien,
        type_transaction: typeTransaction,
        gamme: gamme,
        ton: ton,
      });
      onResult(result);
    } catch (err: any) {
      onError(err.message || 'Erreur lors de la réécriture');
    } finally {
      setLoading(false);
      setLoadingType(null);
    }
  };

  return (
    <div className="card space-y-6">
      <div>
        <h2 className="text-xl font-semibold text-gray-900 mb-2">
          Collez votre annonce
        </h2>
        <p className="text-gray-500 text-sm mb-4">
          Entrez le texte de votre annonce immobilière brute. Notre IA va le transformer
          en texte professionnel optimisé.
        </p>
        <textarea
          value={texte}
          onChange={(e) => setTexte(e.target.value)}
          placeholder="Ex: Appartement 3 pieces, 65m2, proche metro, balcon, refait neuf, cave..."
          className="textarea h-48"
          disabled={loading}
        />
        <div className="mt-2 text-sm text-gray-400">
          {texte.length} caractères (minimum 50)
        </div>
      </div>

      {/* Sélecteurs */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Type de bien
          </label>
          <select
            value={typeBien}
            onChange={(e) => setTypeBien(e.target.value as TypeBien)}
            className="select"
            disabled={loading}
          >
            <option value="appartement">Appartement</option>
            <option value="maison">Maison</option>
            <option value="terrain">Terrain</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Transaction
          </label>
          <select
            value={typeTransaction}
            onChange={(e) => setTypeTransaction(e.target.value as TypeTransaction)}
            className="select"
            disabled={loading}
          >
            <option value="vente">Vente</option>
            <option value="location">Location</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Gamme
          </label>
          <select
            value={gamme}
            onChange={(e) => setGamme(e.target.value as Gamme)}
            className="select"
            disabled={loading}
          >
            <option value="standard">Standard</option>
            <option value="haut_de_gamme">Haut de gamme</option>
          </select>
        </div>
      </div>

      {/* Boutons d'action */}
      <div className="space-y-4">
        <button
          onClick={handleSubmitAll}
          disabled={loading || texte.length < 50}
          className="w-full btn btn-primary py-3 flex items-center justify-center space-x-2"
        >
          {loadingType === 'all' ? (
            <Loader2 className="w-5 h-5 animate-spin" />
          ) : (
            <Wand2 className="w-5 h-5" />
          )}
          <span>Générer les 3 versions</span>
        </button>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <button
            onClick={() => handleSubmitSingle('professionnel')}
            disabled={loading || texte.length < 50}
            className="btn btn-secondary flex items-center justify-center space-x-2"
          >
            {loadingType === 'professionnel' ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <FileText className="w-4 h-4" />
            )}
            <span>Version pro</span>
          </button>

          <button
            onClick={() => handleSubmitSingle('court')}
            disabled={loading || texte.length < 50}
            className="btn btn-secondary flex items-center justify-center space-x-2"
          >
            {loadingType === 'court' ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Zap className="w-4 h-4" />
            )}
            <span>Version courte</span>
          </button>

          <button
            onClick={() => handleSubmitSingle('premium')}
            disabled={loading || texte.length < 50}
            className="btn btn-secondary flex items-center justify-center space-x-2"
          >
            {loadingType === 'premium' ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Heart className="w-4 h-4" />
            )}
            <span>Version premium</span>
          </button>
        </div>
      </div>
    </div>
  );
}
