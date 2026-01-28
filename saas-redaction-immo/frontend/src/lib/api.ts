// Client API pour communiquer avec le backend
import Cookies from 'js-cookie';
import type {
  User,
  RewriteRequest,
  RewriteResponse,
  UsageResponse,
  AnnonceList,
} from '@/types';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
    this.name = 'ApiError';
  }
}

async function fetchApi<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = Cookies.get('token');

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}/api${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ detail: 'Erreur inconnue' }));
    throw new ApiError(response.status, error.detail || 'Erreur serveur');
  }

  if (response.status === 204) {
    return {} as T;
  }

  return response.json();
}

// Auth
export async function register(email: string, password: string): Promise<User> {
  return fetchApi<User>('/auth/register', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

export async function login(email: string, password: string): Promise<{ access_token: string }> {
  const response = await fetchApi<{ access_token: string; token_type: string }>(
    '/auth/login',
    {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }
  );

  Cookies.set('token', response.access_token, { expires: 7 });
  return response;
}

export async function logout(): Promise<void> {
  Cookies.remove('token');
}

export async function getMe(): Promise<User> {
  return fetchApi<User>('/auth/me');
}

// Annonces
export async function rewriteAnnonce(data: RewriteRequest): Promise<RewriteResponse> {
  return fetchApi<RewriteResponse>('/annonces/rewrite', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function rewriteSingleVersion(data: RewriteRequest): Promise<RewriteResponse> {
  return fetchApi<RewriteResponse>('/annonces/rewrite-single', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getUsage(): Promise<UsageResponse> {
  return fetchApi<UsageResponse>('/annonces/usage');
}

export async function getHistory(page: number = 1, perPage: number = 10): Promise<AnnonceList> {
  return fetchApi<AnnonceList>(`/annonces/history?page=${page}&per_page=${perPage}`);
}

export async function getAnnonce(id: number): Promise<RewriteResponse> {
  return fetchApi<RewriteResponse>(`/annonces/${id}`);
}

export async function deleteAnnonce(id: number): Promise<void> {
  return fetchApi<void>(`/annonces/${id}`, {
    method: 'DELETE',
  });
}

// Subscriptions
export async function createCheckout(): Promise<{ checkout_url: string; session_id: string }> {
  return fetchApi<{ checkout_url: string; session_id: string }>(
    '/subscriptions/create-checkout',
    {
      method: 'POST',
    }
  );
}

export async function getSubscriptionStatus(): Promise<{ plan: string; subscription: any }> {
  return fetchApi<{ plan: string; subscription: any }>('/subscriptions/status');
}

export async function createPortalSession(): Promise<{ portal_url: string }> {
  return fetchApi<{ portal_url: string }>('/subscriptions/portal', {
    method: 'POST',
  });
}

export { ApiError };
