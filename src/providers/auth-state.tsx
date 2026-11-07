import * as SecureStore from "expo-secure-store";
import React, { createContext, useContext, useEffect, useState, useCallback, type ReactNode } from "react";

export type UserPlan = "free" | "premium" | "ultra";

export type UserProfile = {
  id?: number;
  uid?: string;
  email: string;
  full_name: string;
  avatar_url?: string | null;
  plan_id: UserPlan;
  plan_name?: string;
  plan_expires_at?: string | null;
  status?: string;
  created_at?: string;
  ai_models?: string[];
  usage_limits?: Record<string, unknown>;
  is_guest?: boolean;
};

type AuthContextType = {
  user: UserProfile;
  token: string | null;
  isLoading: boolean;
  login: (email: string, pass: string) => Promise<{ success: boolean; message?: string }>;
  signup: (fullName: string, email: string, pass: string, terms: boolean) => Promise<{ success: boolean; message?: string }>;
  logout: () => Promise<void>;
  refreshProfile: () => Promise<void>;
};

const GUEST_USER: UserProfile = {
  uid: "guest_local",
  email: "guest@stylesheet.agent",
  full_name: "Guest Explorer",
  plan_id: "free",
  plan_name: "Free Explorer",
  status: "active",
  is_guest: true,
};

const AuthContext = createContext<AuthContextType | null>(null);

const TOKEN_KEY = "stylesheet_auth_token";
const PROFILE_KEY = "stylesheet_user_profile";
const API_BASE = "https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/admin-panel";

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<UserProfile>(GUEST_USER);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Load stored credentials on startup
  useEffect(() => {
    async function loadAuth() {
      try {
        const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);
        const storedProfile = await SecureStore.getItemAsync(PROFILE_KEY);
        if (storedToken && storedProfile) {
          setToken(storedToken);
          setUser(JSON.parse(storedProfile));
        }
      } catch (err) {
        console.warn("Failed to read secure auth store", err);
      } finally {
        setIsLoading(false);
      }
    }
    loadAuth();
  }, []);

  const login = useCallback(async (email: string, pass: string) => {
    try {
      // In local mode or mock, fallback smoothly
      const demoUser: UserProfile = {
        uid: "usr_stylesheet_pro",
        email: email.trim().toLowerCase(),
        full_name: email.split("@")[0] || "Stylesheet Agent",
        plan_id: "premium",
        plan_name: "Premium Pro",
        plan_expires_at: new Date(Date.now() + 30 * 86400000).toISOString(),
        status: "active",
        created_at: new Date().toISOString(),
        is_guest: false,
      };
      const demoToken = "jwt_stylesheet_mock_token_verified";

      setUser(demoUser);
      setToken(demoToken);
      await SecureStore.setItemAsync(TOKEN_KEY, demoToken);
      await SecureStore.setItemAsync(PROFILE_KEY, JSON.stringify(demoUser));
      return { success: true };
    } catch (err) {
      return { success: false, message: (err as Error).message };
    }
  }, []);

  const signup = useCallback(async (fullName: string, email: string, _pass: string, _terms: boolean) => {
    try {
      const newUser: UserProfile = {
        uid: "usr_" + Math.random().toString(36).substring(2, 10),
        email: email.trim().toLowerCase(),
        full_name: fullName.trim(),
        plan_id: "free",
        plan_name: "Free Explorer",
        plan_expires_at: new Date(Date.now() + 365 * 86400000).toISOString(),
        status: "active",
        created_at: new Date().toISOString(),
        is_guest: false,
      };
      const newToken = "jwt_stylesheet_token_" + Date.now();

      setUser(newUser);
      setToken(newToken);
      await SecureStore.setItemAsync(TOKEN_KEY, newToken);
      await SecureStore.setItemAsync(PROFILE_KEY, JSON.stringify(newUser));
      return { success: true };
    } catch (err) {
      return { success: false, message: (err as Error).message };
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      await SecureStore.deleteItemAsync(PROFILE_KEY);
    } catch {
      // ignore
    }
    setUser(GUEST_USER);
    setToken(null);
  }, []);

  const refreshProfile = useCallback(async () => {
    if (!token) return;
    try {
      const stored = await SecureStore.getItemAsync(PROFILE_KEY);
      if (stored) {
        setUser(JSON.parse(stored));
      }
    } catch (err) {
      console.warn("Error refreshing profile", err);
    }
  }, [token]);

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        login,
        signup,
        logout,
        refreshProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextType {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return ctx;
}
