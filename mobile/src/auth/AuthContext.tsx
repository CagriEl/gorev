import * as SecureStore from 'expo-secure-store';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { fetchMe, login as apiLogin, revokeToken } from '../api/client';
import { unregisterDevicePushToken } from '../notifications/register';
import type { User } from '../types';

const TOKEN_KEY = 'bel_access_token';
const USER_KEY = 'bel_user';

type AuthContextValue = {
  user: User | null;
  token: string | null;
  loading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);
        const storedUser = await SecureStore.getItemAsync(USER_KEY);
        if (storedToken && storedUser) {
          setToken(storedToken);
          setUser(JSON.parse(storedUser) as User);
          const fresh = await fetchMe(storedToken);
          setUser(fresh);
          await SecureStore.setItemAsync(USER_KEY, JSON.stringify(fresh));
        }
      } catch {
        await SecureStore.deleteItemAsync(TOKEN_KEY);
        await SecureStore.deleteItemAsync(USER_KEY);
        setToken(null);
        setUser(null);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const { access_token, user: loggedIn } = await apiLogin(email, password);
    await SecureStore.setItemAsync(TOKEN_KEY, access_token);
    await SecureStore.setItemAsync(USER_KEY, JSON.stringify(loggedIn));
    setToken(access_token);
    setUser(loggedIn);
  }, []);

  const signOut = useCallback(async () => {
    if (token) {
      try {
        await unregisterDevicePushToken(token);
      } catch {
        // ignore
      }
      try {
        await revokeToken(token);
      } catch {
        // ignore
      }
    }
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    await SecureStore.deleteItemAsync(USER_KEY);
    setToken(null);
    setUser(null);
  }, [token]);

  const refreshUser = useCallback(async () => {
    if (!token) return;
    const fresh = await fetchMe(token);
    setUser(fresh);
    await SecureStore.setItemAsync(USER_KEY, JSON.stringify(fresh));
  }, [token]);

  const value = useMemo(
    () => ({ user, token, loading, signIn, signOut, refreshUser }),
    [user, token, loading, signIn, signOut, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth AuthProvider içinde kullanılmalı');
  return ctx;
}
