'use client';

import { useState } from 'react';
import { Copy, Check, FileText, Zap, Heart, BarChart3 } from 'lucide-react';
import type { RewriteResponse } from '@/types';

interface ResultDisplayProps {
  result: RewriteResponse;
}

function ScoreBadge({ score, label }: { score: number; label: string }) {
  const getColorClass = (score: number) => {
    if (score >= 75) return 'score-green';
    if (score >= 50) return 'score-orange';
    return 'score-red';
  };

  return (
    <div className="flex flex-col items-center p-3 bg-gray-50 rounded-lg">
      <span className="text-sm text-gray-500 mb-1">{label}</span>
      <span className={`score-badge ${getColorClass(score)}`}>
        {Math.round(score)}/100
      </span>
    </div>
  );
}

function TextBlock({
  title,
  text,
  icon: Icon,
}: {
  title: string;
  text: string | null;
  icon: React.ComponentType<{ className?: string }>;
}) {
  const [copied, setCopied] = useState(false);

  if (!text) return null;

  const handleCopy = async () => {
    await navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="border border-gray-200 rounded-lg overflow-hidden">
      <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
        <div className="flex items-center space-x-2">
          <Icon className="w-4 h-4 text-primary-600" />
          <span className="font-medium text-gray-900">{title}</span>
        </div>
        <button
          onClick={handleCopy}
          className="flex items-center space-x-1 text-sm text-gray-500 hover:text-primary-600 transition-colors"
        >
          {copied ? (
            <>
              <Check className="w-4 h-4 text-green-500" />
              <span className="text-green-500">Copié !</span>
            </>
          ) : (
            <>
              <Copy className="w-4 h-4" />
              <span>Copier</span>
            </>
          )}
        </button>
      </div>
      <div className="p-4">
        <p className="text-gray-700 whitespace-pre-wrap leading-relaxed">{text}</p>
      </div>
    </div>
  );
}

export default function ResultDisplay({ result }: ResultDisplayProps) {
  return (
    <div className="card space-y-6">
      <div>
        <h2 className="text-xl font-semibold text-gray-900 mb-2">
          Résultat de la réécriture
        </h2>
        <p className="text-gray-500 text-sm">
          Voici les versions générées de votre annonce. Cliquez sur "Copier" pour
          utiliser le texte.
        </p>
      </div>

      {/* Scores */}
      <div className="bg-white border border-gray-200 rounded-lg p-4">
        <div className="flex items-center space-x-2 mb-4">
          <BarChart3 className="w-5 h-5 text-primary-600" />
          <span className="font-medium text-gray-900">Score qualité</span>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <ScoreBadge score={result.scores.global_score} label="Global" />
          <ScoreBadge score={result.scores.clarte} label="Clarté" />
          <ScoreBadge score={result.scores.attractivite} label="Attractivité" />
          <ScoreBadge score={result.scores.lisibilite} label="Lisibilité" />
        </div>
      </div>

      {/* Comparaison Avant/Après */}
      <div className="border border-gray-200 rounded-lg overflow-hidden">
        <div className="px-4 py-3 bg-gray-100 border-b border-gray-200">
          <span className="font-medium text-gray-700">Texte original</span>
        </div>
        <div className="p-4 bg-gray-50">
          <p className="text-gray-600 text-sm whitespace-pre-wrap">
            {result.texte_original}
          </p>
        </div>
      </div>

      {/* Versions générées */}
      <div className="space-y-4">
        <TextBlock
          title="Version Professionnelle"
          text={result.texte_professionnel}
          icon={FileText}
        />
        <TextBlock
          title="Version Courte"
          text={result.texte_court}
          icon={Zap}
        />
        <TextBlock
          title="Version Premium Émotionnelle"
          text={result.texte_premium}
          icon={Heart}
        />
      </div>
    </div>
  );
}
