// Types pour l'application

export type TypeBien = 'appartement' | 'maison' | 'terrain';
export type TypeTransaction = 'vente' | 'location';
export type Gamme = 'standard' | 'haut_de_gamme';
export type TonRedaction = 'professionnel' | 'court' | 'premium';
export type PlanType = 'free' | 'pro';

export interface User {
  id: number;
  email: string;
  plan: PlanType;
  is_verified: boolean;
  created_at: string;
}

export interface ScoreQualite {
  clarte: number;
  attractivite: number;
  lisibilite: number;
  global_score: number;
}

export interface RewriteRequest {
  texte_original: string;
  type_bien: TypeBien;
  type_transaction: TypeTransaction;
  gamme: Gamme;
  ton: TonRedaction;
}

export interface RewriteResponse {
  id: number;
  texte_original: string;
  texte_professionnel: string | null;
  texte_court: string | null;
  texte_premium: string | null;
  scores: ScoreQualite;
  type_bien: TypeBien;
  type_transaction: TypeTransaction;
  gamme: Gamme;
  created_at: string;
}

export interface UsageResponse {
  used: number;
  limit: number;
  remaining: number;
  plan: PlanType;
}

export interface AnnonceListItem {
  id: number;
  texte_original: string;
  type_bien: TypeBien;
  type_transaction: TypeTransaction;
  score_global: number | null;
  created_at: string;
}

export interface AnnonceList {
  items: AnnonceListItem[];
  total: number;
  page: number;
  per_page: number;
}
