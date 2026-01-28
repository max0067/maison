'use client';

import { useState, useEffect, useCallback } from 'react';
import Cookies from 'js-cookie';
import { getMe, logout as apiLogout } from '@/lib/api';
import type { User } from '@/types';

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchUser = useCallback(async () => {
    const token = Cookies.get('token');
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }

    try {
      const userData = await getMe();
      setUser(userData);
      setError(null);
    } catch (err) {
      setUser(null);
      Cookies.remove('token');
      setError('Session expirée');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchUser();
  }, [fetchUser]);

  const logout = useCallback(async () => {
    await apiLogout();
    setUser(null);
  }, []);

  const refresh = useCallback(async () => {
    setLoading(true);
    await fetchUser();
  }, [fetchUser]);

  return {
    user,
    loading,
    error,
    logout,
    refresh,
    isAuthenticated: !!user,
  };
}
